<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Services\ChecklistService;
use Livewire\Component;
use Livewire\Attributes\Url;

class ProjectTaskList extends Component
{
    #[Url]
    public ?int $selectedProjectId = null;

    public ?int $selectedTaskId = null;

    // Task form fields
    public string $taskName    = '';
    public string $taskContent = '';
    public string $taskStatus  = 'todo';
    public string $taskDueDate = '';

    // Modal state
    public bool $showTaskModal  = false;
    public bool $showCreateTask = false;

    // New project/client
    public string $newProjectName = '';
    public ?int   $newProjectClientId = null;
    public string $newClientName = '';
    public bool   $showNewClientForm = false;

    public function selectProject(int $projectId): void
    {
        $this->selectedProjectId = $projectId;
        $this->selectedTaskId    = null;
        $this->showTaskModal     = false;
    }

    public function openTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        $this->selectedTaskId = $taskId;
        $this->taskName       = $task->task_name;
        $this->taskContent    = $task->content ?? '';
        $this->taskStatus     = $task->status;
        $this->taskDueDate    = $task->due_date?->format('Y-m-d') ?? '';
        $this->showTaskModal  = true;
    }

    public function saveTask(): void
    {
        $this->validate([
            'taskName'    => 'required|string|max:255',
            'taskStatus'  => 'required|in:todo,in_progress,done',
            'taskDueDate' => 'nullable|date',
        ]);

        Task::where('id', $this->selectedTaskId)->update([
            'task_name' => $this->taskName,
            'content'   => $this->taskContent,
            'status'    => $this->taskStatus,
            'due_date'  => $this->taskDueDate ?: null,
        ]);

        // Re-fetch to trigger model events for hashtag sync
        $task = Task::find($this->selectedTaskId);
        $task->content = $this->taskContent;
        $task->save();

        $this->showTaskModal = false;
        session()->flash('success', 'Task saved.');
    }

    public function createTask(): void
    {
        $this->validate([
            'taskName'    => 'required|string|max:255',
            'taskStatus'  => 'required|in:todo,in_progress,done',
            'taskDueDate' => 'nullable|date',
        ]);

        Task::create([
            'project_id' => $this->selectedProjectId,
            'task_name'  => $this->taskName,
            'content'    => $this->taskContent,
            'status'     => $this->taskStatus,
            'due_date'   => $this->taskDueDate ?: null,
        ]);

        $this->resetTaskForm();
        $this->showCreateTask = false;
        session()->flash('success', 'Task created.');
    }

    public function toggleChecklist(int $taskId, int $itemIndex): void
    {
        $task = Task::findOrFail($taskId);
        $task->content = app(ChecklistService::class)->toggle($task->content ?? '', $itemIndex);
        $task->save();

        if ($this->selectedTaskId === $taskId) {
            $this->taskContent = $task->content;
        }
    }

    public function deleteTask(int $taskId): void
    {
        Task::destroy($taskId);
        $this->showTaskModal = false;
    }

    public function createProject(): void
    {
        $this->validate([
            'newProjectName'     => 'required|string|max:255',
            'newProjectClientId' => 'required|exists:clients,id',
        ]);

        $project = Project::create([
            'client_id'    => $this->newProjectClientId,
            'project_name' => $this->newProjectName,
        ]);

        $this->newProjectName     = '';
        $this->newProjectClientId = null;
        $this->selectedProjectId  = $project->id;
    }

    public function createClient(): void
    {
        $this->validate(['newClientName' => 'required|string|max:255']);

        $client = Client::create(['name' => $this->newClientName]);
        $this->newProjectClientId = $client->id;
        $this->newClientName      = '';
        $this->showNewClientForm  = false;
    }

    private function resetTaskForm(): void
    {
        $this->taskName    = '';
        $this->taskContent = '';
        $this->taskStatus  = 'todo';
        $this->taskDueDate = '';
    }

    public function render()
    {
        $clients = Client::with(['projects' => fn($q) => $q->withCount('tasks')])->orderBy('name')->get();

        $tasks = $this->selectedProjectId
            ? Task::where('project_id', $this->selectedProjectId)->orderBy('due_date')->get()
            : collect();

        $selectedTask = $this->selectedTaskId ? Task::find($this->selectedTaskId) : null;
        $checklist    = $selectedTask ? app(ChecklistService::class)->parse($selectedTask->content ?? '') : [];

        return view('livewire.project-task-list', compact('clients', 'tasks', 'selectedTask', 'checklist'));
    }
}
