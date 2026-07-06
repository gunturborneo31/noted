<?php

namespace App\Livewire;

use App\Models\Note;
use App\Models\NoteCredential;
use Livewire\Component;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;

class NoteEditor extends Component
{
    #[Url]
    public ?int $noteId = null;

    public string $title       = '';
    public string $body        = '';
    public bool   $isEditing   = false;
    public bool   $showNewForm = false;
    public string $newTitle    = '';

    // Credential form
    public bool   $showCredentialForm  = false;
    public string $credUrlLogin        = '';
    public string $credUsername        = '';
    public string $credPassword        = '';
    public ?int   $editingCredentialId = null;
    public bool   $showPasswords       = false;

    public function selectNote(int $id): void
    {
        $note         = Note::findOrFail($id);
        $this->noteId = $id;
        $this->title  = $note->title;
        $this->body   = $note->body ?? '';
        $this->isEditing = false;
        $this->resetCredentialForm();
    }

    public function startEdit(): void
    {
        $this->isEditing = true;
    }

    public function cancelEdit(): void
    {
        if ($this->noteId) {
            $note = Note::find($this->noteId);
            $this->title = $note->title;
            $this->body  = $note->body ?? '';
        }
        $this->isEditing = false;
    }

    public function saveNote(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'body'  => 'nullable|string',
        ]);

        $note = Note::find($this->noteId);
        $note->update([
            'title' => $this->title,
            'body'  => $this->body,
        ]);

        $this->isEditing = false;
        session()->flash('success', 'Note saved.');
    }

    public function createNote(): void
    {
        $this->validate(['newTitle' => 'required|string|max:255']);

        $note = Note::create([
            'user_id' => auth()->id() ?? 1, // fallback for demo
            'title'   => $this->newTitle,
            'body'    => '',
            'sort_order' => ((int) Note::where('user_id', auth()->id() ?? 1)->max('sort_order')) + 1,
        ]);

        $this->newTitle    = '';
        $this->showNewForm = false;
        $this->selectNote($note->id);
    }

    public function deleteNote(int $id): void
    {
        Note::destroy($id);
        $this->noteId    = null;
        $this->title     = '';
        $this->body      = '';
        $this->isEditing = false;
    }

    public function moveNoteUp(int $id): void
    {
        $this->moveNote($id, -1);
    }

    public function moveNoteDown(int $id): void
    {
        $this->moveNote($id, 1);
    }

    public function reorderNotes(array $orderedIds): void
    {
        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));
        $userId = auth()->id() ?? 1;

        $siblings = Note::where('user_id', $userId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        if (count($siblings) < 2 || count($orderedIds) !== count($siblings)) {
            return;
        }

        $expected = $siblings;
        sort($expected);
        $received = $orderedIds;
        sort($received);

        if ($expected !== $received) {
            return;
        }

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $noteId) {
                Note::where('id', $noteId)->update(['sort_order' => $index + 1]);
            }
        });
    }

    // --- Credentials ---

    public function saveCredential(): void
    {
        $this->validate([
            'credUrlLogin'  => 'nullable|url|max:500',
            'credUsername'  => 'nullable|string|max:255',
            'credPassword'  => 'required|string|max:500',
        ]);

        if ($this->editingCredentialId) {
            NoteCredential::where('id', $this->editingCredentialId)->update([
                'url_login' => $this->credUrlLogin ?: null,
                'username'  => $this->credUsername ?: null,
                'password'  => $this->credPassword,
            ]);
        } else {
            NoteCredential::create([
                'note_id'   => $this->noteId,
                'url_login' => $this->credUrlLogin ?: null,
                'username'  => $this->credUsername ?: null,
                'password'  => $this->credPassword,
            ]);
        }

        $this->resetCredentialForm();
    }

    public function editCredential(int $id): void
    {
        $cred = NoteCredential::findOrFail($id);
        $this->editingCredentialId = $id;
        $this->credUrlLogin        = $cred->url_login ?? '';
        $this->credUsername        = $cred->username ?? '';
        $this->credPassword        = $cred->password;
        $this->showCredentialForm  = true;
    }

    public function deleteCredential(int $id): void
    {
        NoteCredential::destroy($id);
    }

    private function resetCredentialForm(): void
    {
        $this->showCredentialForm  = false;
        $this->editingCredentialId = null;
        $this->credUrlLogin        = '';
        $this->credUsername        = '';
        $this->credPassword        = '';
    }

    public function render()
    {
        $notes = Note::where('user_id', auth()->id() ?? 1)
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get();

        $currentNote  = $this->noteId ? Note::with('credentials', 'hashtags')->find($this->noteId) : null;
        $credentials  = $currentNote?->credentials ?? collect();

        return view('livewire.note-editor', compact('notes', 'currentNote', 'credentials'));
    }

    private function moveNote(int $noteId, int $direction): void
    {
        $note = Note::findOrFail($noteId);

        $siblings = Note::where('user_id', $note->user_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order'])
            ->values();

        if ($siblings->count() < 2) {
            return;
        }

        foreach ($siblings as $index => $row) {
            Note::where('id', $row->id)->update(['sort_order' => $index + 1]);
        }

        $siblings = Note::where('user_id', $note->user_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order'])
            ->values();

        $currentIndex = $siblings->search(fn($row) => (int) $row->id === $noteId);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $currentIndex + $direction;
        if ($targetIndex < 0 || $targetIndex >= $siblings->count()) {
            return;
        }

        DB::transaction(function () use ($siblings, $currentIndex, $targetIndex): void {
            $current = $siblings[$currentIndex];
            $target = $siblings[$targetIndex];

            Note::where('id', $current->id)->update(['sort_order' => $target->sort_order]);
            Note::where('id', $target->id)->update(['sort_order' => $current->sort_order]);
        });
    }
}
