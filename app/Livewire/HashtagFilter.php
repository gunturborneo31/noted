<?php

namespace App\Livewire;

use App\Models\Hashtag;
use App\Models\Task;
use App\Models\Note;
use Livewire\Component;
use Livewire\Attributes\Url;

class HashtagFilter extends Component
{
    #[Url]
    public string $tag = '';

    public string $search = '';

    public function mount(): void
    {
        $this->search = $this->tag;
    }

    public function applySearch(): void
    {
        $this->tag = trim(ltrim($this->search, '#'));
    }

    public function filterByTag(string $tag): void
    {
        $this->tag    = $tag;
        $this->search = $tag;
    }

    public function render()
    {
        $hashtag = $this->tag
            ? Hashtag::where('tag_name', strtolower($this->tag))->first()
            : null;

        $tasks = $hashtag
            ? $hashtag->tasks()->with('project.client')->orderBy('due_date')->get()
            : collect();

        $notes = $hashtag
            ? $hashtag->notes()->orderByDesc('updated_at')->get()
            : collect();

        $popularTags = Hashtag::withCount(['tasks', 'notes'])
            ->orderByRaw('(tasks_count + notes_count) DESC')
            ->limit(20)
            ->get();

        return view('livewire.hashtag-filter', compact('hashtag', 'tasks', 'notes', 'popularTags'));
    }
}
