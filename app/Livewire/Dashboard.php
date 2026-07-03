<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Task;
use Livewire\Component;
use Carbon\Carbon;

class Dashboard extends Component
{
    public function render()
    {
        $today = Carbon::today();

        $stats = [
            'todo'        => Task::where('status', 'todo')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'done'        => Task::where('status', 'done')->count(),
            'overdue'     => Task::whereIn('status', ['todo', 'in_progress'])
                                ->whereDate('due_date', '<', $today)
                                ->count(),
        ];

        $activeProjects = Project::where('status', 'active')
            ->withCount([
                'tasks',
                'tasks as todo_count'        => fn($q) => $q->where('status', 'todo'),
                'tasks as in_progress_count' => fn($q) => $q->where('status', 'in_progress'),
                'tasks as done_count'        => fn($q) => $q->where('status', 'done'),
            ])
            ->with('client')
            ->latest()
            ->get();

        $urgentTasks = Task::whereIn('status', ['todo', 'in_progress'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->with('project.client')
            ->limit(5)
            ->get();

        return view('livewire.dashboard', compact('stats', 'activeProjects', 'urgentTasks'));
    }
}
