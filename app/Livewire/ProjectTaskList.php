<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Services\ChecklistService;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ProjectTaskList extends Component
{
    use WithPagination;

    #[Url]
    public int $perPage = 10;

    #[Url]
    public ?int $selectedProjectId = null;

    #[Url]
    public string $viewMode = 'project';

    #[Url]
    public string $allSearch = '';

    #[Url]
    public string $allStatus = '';

    public array $statusFilters = [
        'todo' => true,
        'in_progress' => true,
        'done' => false,
    ];

    #[Url]
    public ?int $allClientId = null;

    #[Url]
    public ?int $allProjectId = null;

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
    public bool $manageMode = false;

    public ?int $editingClientId = null;
    public string $editingClientName = '';
    public ?int $editingProjectId = null;
    public string $editingProjectName = '';
    public bool $positionMode = false;

    public function selectProject(int $projectId): void
    {
        $this->selectedProjectId = $projectId;
        $this->viewMode          = 'project';
        $this->selectedTaskId    = null;
        $this->showTaskModal     = false;
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        if (!in_array($mode, ['project', 'all'], true)) {
            return;
        }

        $this->viewMode = $mode;
        $this->selectedTaskId = null;
        $this->showTaskModal = false;
        $this->showCreateTask = false;
        $this->resetPage();
    }

    public function updatedAllClientId($value): void
    {
        if (empty($value)) {
            $this->allClientId = null;
            $this->allProjectId = null;
            $this->resetPage();
            return;
        }

        $this->allClientId = (int) $value;

        if ($this->allProjectId) {
            $isProjectInClient = Project::where('id', $this->allProjectId)
                ->where('client_id', $this->allClientId)
                ->exists();

            if (!$isProjectInClient) {
                $this->allProjectId = null;
            }
        }

        $this->resetPage();
    }

    public function updatedAllProjectId($value): void
    {
        $this->allProjectId = empty($value) ? null : (int) $value;
        $this->resetPage();
    }

    public function updatedAllSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAllStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage($value): void
    {
        $allowed = [5, 10, 25, 50, 100];
        $this->perPage = in_array((int) $value, $allowed, true) ? (int) $value : 10;
        $this->resetPage();
    }

    public function updatedStatusFilters(): void
    {
        $this->resetPage();
    }

    public function resetAllFilters(): void
    {
        $this->allSearch = '';
        $this->allStatus = '';
        $this->allClientId = null;
        $this->allProjectId = null;
        $this->statusFilters = [
            'todo' => true,
            'in_progress' => true,
            'done' => false,
        ];
        $this->resetPage();
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

        $maxOrder = (int) Task::where('project_id', $this->selectedProjectId)->max('sort_order');

        Task::create([
            'project_id' => $this->selectedProjectId,
            'task_name'  => $this->taskName,
            'content'    => $this->taskContent,
            'status'     => $this->taskStatus,
            'due_date'   => $this->taskDueDate ?: null,
            'sort_order' => $maxOrder + 1,
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

    public function updateTaskStatus(int $taskId, string $status): void
    {
        if (!in_array($status, ['todo', 'in_progress', 'done'], true)) {
            return;
        }

        $task = Task::findOrFail($taskId);
        $task->update(['status' => $status]);

        if ($this->selectedTaskId === $taskId) {
            $this->taskStatus = $status;
        }

        session()->flash('success', 'Task status updated.');
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
            'sort_order'   => ((int) Project::where('client_id', $this->newProjectClientId)->max('sort_order')) + 1,
        ]);

        $this->newProjectName     = '';
        $this->newProjectClientId = null;
        $this->selectedProjectId  = $project->id;
    }

    public function createClient(): void
    {
        $this->validate(['newClientName' => 'required|string|max:255']);

        $client = Client::create([
            'name' => $this->newClientName,
            'sort_order' => ((int) Client::max('sort_order')) + 1,
        ]);
        $this->newProjectClientId = $client->id;
        $this->newClientName      = '';
        $this->showNewClientForm  = false;
    }

    public function toggleManageMode(): void
    {
        $this->manageMode = !$this->manageMode;

        if (!$this->manageMode) {
            $this->cancelEditClient();
            $this->cancelEditProject();
        }
    }

    public function startEditClient(int $clientId): void
    {
        $client = Client::findOrFail($clientId);
        $this->editingClientId = $client->id;
        $this->editingClientName = $client->name;
    }

    public function cancelEditClient(): void
    {
        $this->editingClientId = null;
        $this->editingClientName = '';
    }

    public function saveClientName(): void
    {
        if (!$this->editingClientId) {
            return;
        }

        $this->validate([
            'editingClientName' => 'required|string|max:255',
        ]);

        Client::where('id', $this->editingClientId)->update([
            'name' => trim($this->editingClientName),
        ]);

        $this->cancelEditClient();
        session()->flash('success', 'Nama client berhasil diperbarui.');
    }

    public function deleteClient(int $clientId): void
    {
        $client = Client::with('projects:id,client_id')->findOrFail($clientId);
        $projectIds = $client->projects->pluck('id')->all();

        if ($this->selectedProjectId && in_array($this->selectedProjectId, $projectIds, true)) {
            $this->selectedProjectId = null;
            $this->selectedTaskId = null;
            $this->showTaskModal = false;
            $this->showCreateTask = false;
        }

        if ($this->allClientId === $clientId) {
            $this->allClientId = null;
            $this->allProjectId = null;
        }

        if ($this->editingClientId === $clientId) {
            $this->cancelEditClient();
        }

        Client::where('id', $clientId)->delete();
        session()->flash('success', 'Client berhasil dihapus.');
    }

    public function startEditProject(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $this->editingProjectId = $project->id;
        $this->editingProjectName = $project->project_name;
    }

    public function cancelEditProject(): void
    {
        $this->editingProjectId = null;
        $this->editingProjectName = '';
    }

    public function saveProjectName(): void
    {
        if (!$this->editingProjectId) {
            return;
        }

        $this->validate([
            'editingProjectName' => 'required|string|max:255',
        ]);

        Project::where('id', $this->editingProjectId)->update([
            'project_name' => trim($this->editingProjectName),
        ]);

        $this->cancelEditProject();
        session()->flash('success', 'Nama project berhasil diperbarui.');
    }

    public function deleteProject(int $projectId): void
    {
        if ($this->selectedProjectId === $projectId) {
            $this->selectedProjectId = null;
            $this->selectedTaskId = null;
            $this->showTaskModal = false;
            $this->showCreateTask = false;
        }

        if ($this->allProjectId === $projectId) {
            $this->allProjectId = null;
        }

        if ($this->editingProjectId === $projectId) {
            $this->cancelEditProject();
        }

        Project::where('id', $projectId)->delete();
        session()->flash('success', 'Project berhasil dihapus.');
    }

    public function moveTaskUp(int $taskId): void
    {
        $this->moveTask($taskId, -1);
    }

    public function moveTaskDown(int $taskId): void
    {
        $this->moveTask($taskId, 1);
    }

    public function togglePositionMode(): void
    {
        $this->positionMode = !$this->positionMode;
    }

    public function reorderTasks(array $orderedIds): void
    {
        if (!$this->selectedProjectId) {
            return;
        }

        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));

        $siblings = Task::where('project_id', $this->selectedProjectId)
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
            foreach ($orderedIds as $index => $taskId) {
                Task::where('id', $taskId)->update(['sort_order' => $index + 1]);
            }
        });

        $this->dispatch('task-order-saved');
    }

    private function moveTask(int $taskId, int $direction): void
    {
        $task = Task::findOrFail($taskId);

        $siblings = Task::where('project_id', $task->project_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order']);

        // Normalise sort_order so it's a clean sequence
        foreach ($siblings as $index => $row) {
            $row->sort_order = $index + 1;
            $row->save();
        }

        $siblings = Task::where('project_id', $task->project_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order'])
            ->values();

        $currentIndex = $siblings->search(fn($row) => (int) $row->id === $taskId);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $currentIndex + $direction;
        if ($targetIndex < 0 || $targetIndex >= $siblings->count()) {
            return;
        }

        DB::transaction(function () use ($siblings, $currentIndex, $targetIndex): void {
            $current = $siblings[$currentIndex];
            $target  = $siblings[$targetIndex];

            Task::where('id', $current->id)->update(['sort_order' => $target->sort_order]);
            Task::where('id', $target->id)->update(['sort_order' => $current->sort_order]);
        });
    }

    public function reorderClients(array $orderedIds): void
    {
        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));

        $allClients = Client::orderBy('sort_order')->orderBy('id')->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        if (count($allClients) < 2 || count($orderedIds) !== count($allClients)) {
            return;
        }

        $expected = $allClients;
        sort($expected);
        $received = $orderedIds;
        sort($received);

        if ($expected !== $received) {
            return;
        }

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $clientId) {
                Client::where('id', $clientId)->update(['sort_order' => $index + 1]);
            }
        });

        $this->dispatch('client-order-saved');
    }

    public function moveProjectUp(int $projectId): void
    {
        $this->moveProject($projectId, -1);
    }

    public function moveProjectDown(int $projectId): void
    {
        $this->moveProject($projectId, 1);
    }

    public function reorderProjects(int $clientId, array $orderedIds): void
    {
        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));

        $siblings = Project::where('client_id', $clientId)
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
            foreach ($orderedIds as $index => $projectId) {
                Project::where('id', $projectId)->update(['sort_order' => $index + 1]);
            }
        });

        $this->dispatch('project-order-saved');
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
        if (!in_array($this->viewMode, ['project', 'all'], true)) {
            $this->viewMode = 'project';
        }

        $clients = Client::with(['projects' => fn($q) => $q
            ->withCount([
                'tasks as open_tasks_count' => fn($taskQuery) => $taskQuery->where('status', '!=', 'done'),
            ])
            ->orderBy('sort_order')
            ->orderBy('project_name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $allFilterProjects = collect();
        if ($this->viewMode === 'all') {
            $allFilterProjects = Project::query()
                ->when($this->allClientId, fn($q) => $q->where('client_id', $this->allClientId))
                ->orderBy('sort_order')
                ->orderBy('project_name')
                ->get(['id', 'client_id', 'project_name']);
        }

        $tasks = $this->viewMode === 'all'
            ? Task::with(['project.client', 'hashtags'])
                ->when($this->allSearch !== '', function ($q) {
                    $q->where('task_name', 'like', '%'.$this->allSearch.'%');
                })
                ->when(count($this->activeStatuses()) === 0, function ($q) {
                    $q->whereRaw('1 = 0');
                })
                ->when(count($this->activeStatuses()) > 0, function ($q) {
                    $q->whereIn('status', $this->activeStatuses());
                })
                ->when($this->allStatus !== '', function ($q) {
                    $q->where('status', $this->allStatus);
                })
                ->when($this->allProjectId, function ($q) {
                    $q->where('project_id', $this->allProjectId);
                })
                ->when($this->allClientId && !$this->allProjectId, function ($q) {
                    $q->whereHas('project', fn($p) => $p->where('client_id', $this->allClientId));
                })
                ->orderByRaw("CASE status WHEN 'todo' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
                ->orderBy('due_date')
                ->orderByDesc('id')
                ->paginate($this->perPage)
            : ($this->selectedProjectId
                ? Task::with('hashtags')
                    ->where('project_id', $this->selectedProjectId)
                    ->when(count($this->activeStatuses()) === 0, function ($q) {
                        $q->whereRaw('1 = 0');
                    })
                    ->when(count($this->activeStatuses()) > 0, function ($q) {
                        $q->whereIn('status', $this->activeStatuses());
                    })
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->paginate($this->perPage)
                : collect());

        $selectedTask = $this->selectedTaskId ? Task::find($this->selectedTaskId) : null;
        $checklist    = $selectedTask ? app(ChecklistService::class)->parse($selectedTask->content ?? '') : [];

        return view('livewire.project-task-list', compact('clients', 'tasks', 'selectedTask', 'checklist', 'allFilterProjects'));
    }

    private function moveProject(int $projectId, int $direction): void
    {
        $project = Project::findOrFail($projectId);

        $siblings = Project::where('client_id', $project->client_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order']);

        if ($siblings->count() < 2) {
            return;
        }

        foreach ($siblings as $index => $row) {
            $row->sort_order = $index + 1;
            $row->save();
        }

        $siblings = Project::where('client_id', $project->client_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order'])
            ->values();

        $currentIndex = $siblings->search(fn($row) => (int) $row->id === $projectId);
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

            Project::where('id', $current->id)->update(['sort_order' => $target->sort_order]);
            Project::where('id', $target->id)->update(['sort_order' => $current->sort_order]);
        });
    }

    private function activeStatuses(): array
    {
        $statuses = [];

        foreach (['todo', 'in_progress', 'done'] as $status) {
            if (!empty($this->statusFilters[$status])) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }
}
