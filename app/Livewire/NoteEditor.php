<?php

namespace App\Livewire;

use App\Models\Note;
use App\Models\NoteCredential;
use Livewire\Component;
use Livewire\Attributes\Url;

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
        $notes = Note::orderByDesc('updated_at')->get();

        $currentNote  = $this->noteId ? Note::with('credentials', 'hashtags')->find($this->noteId) : null;
        $credentials  = $currentNote?->credentials ?? collect();

        return view('livewire.note-editor', compact('notes', 'currentNote', 'credentials'));
    }
}
