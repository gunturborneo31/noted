    <div class="space-y-4" x-data="{ orderSaved: false, positionSaved: false, showSaved(){ this.orderSaved = true; clearTimeout(this._savedTimer); this._savedTimer = setTimeout(() => this.orderSaved = false, 1000); }, showPositionSaved(){ this.positionSaved = true; clearTimeout(this._posSavedTimer); this._posSavedTimer = setTimeout(() => this.positionSaved = false, 1000); } }" x-on:project-order-saved.window="showSaved()" x-on:task-order-saved.window="showPositionSaved()">
    <div class="neo-card p-3 bg-lime-50 space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-black uppercase">View:</span>
            <button wire:click="setViewMode('project')"
                    class="neo-btn text-sm px-3 py-1 {{ $viewMode === 'project' ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                Per Project
            </button>
            <button wire:click="setViewMode('all')"
                    class="neo-btn text-sm px-3 py-1 {{ $viewMode === 'all' ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                Semua Pekerjaan
            </button>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-black uppercase">Status:</span>
            <label class="inline-flex items-center gap-1 text-xs font-bold border-2 border-black bg-white px-2 py-1 cursor-pointer">
                <input type="checkbox" wire:model.live="statusFilters.todo" class="w-3.5 h-3.5 border-black" />
                To Do
            </label>
            <label class="inline-flex items-center gap-1 text-xs font-bold border-2 border-black bg-white px-2 py-1 cursor-pointer">
                <input type="checkbox" wire:model.live="statusFilters.in_progress" class="w-3.5 h-3.5 border-black" />
                In Progress
            </label>
            <label class="inline-flex items-center gap-1 text-xs font-bold border-2 border-black bg-white px-2 py-1 cursor-pointer">
                <input type="checkbox" wire:model.live="statusFilters.done" class="w-3.5 h-3.5 border-black" />
                Done
            </label>
        </div>
    </div>

<div class="flex flex-col md:flex-row md:gap-6 min-h-[80vh]">

    {{-- Sidebar: Client → Project Tree --}}
    <aside class="w-full md:w-64 shrink-0 border-4 border-black bg-gradient-to-b from-lime-50 to-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] mb-4 md:mb-0 self-start
        {{ $viewMode === 'all' ? 'hidden' : ($selectedProjectId ? 'hidden md:block' : 'block') }}">
        <div class="border-b-4 border-black bg-lime-400 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-black uppercase text-sm">Projects</span>
                <span x-show="orderSaved" x-transition.opacity class="text-[10px] font-black uppercase border-2 border-black bg-black text-lime-400 px-1.5 py-0.5">Urutan Tersimpan</span>
                <span x-show="clientOrderSaved" x-transition.opacity class="text-[10px] font-black uppercase border-2 border-black bg-black text-lime-400 px-1.5 py-0.5">Client Tersimpan</span>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="toggleManageMode"
                        class="neo-btn px-2 py-0.5 text-xs {{ $manageMode ? 'bg-black text-lime-400' : 'bg-white text-black' }}">Edit</button>
                <button wire:click="$set('showCreateTask', false)"
                        x-data x-on:click="$dispatch('open-new-project')"
                        class="neo-btn bg-black text-lime-400 px-2 py-0.5 text-xs">+ New</button>
            </div>
        </div>

        {{-- New Project Form --}}
        <div x-data="{ show: false }" @open-new-project.window="show = !show">
            <div x-show="show" x-cloak class="border-b-4 border-black p-3 bg-lime-100 space-y-2">
                <select wire:model="newProjectClientId"
                        class="neo-input w-full text-sm">
                    <option value="">— Select Client —</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
                <button wire:click="$set('showNewClientForm', true)"
                        class="text-xs font-bold underline">+ Add Client</button>
                @if($showNewClientForm)
                    <div class="flex gap-1">
                        <input wire:model="newClientName" placeholder="Client name"
                               class="neo-input flex-1 text-sm" />
                        <button wire:click="createClient"
                                class="neo-btn bg-lime-400 border-black text-black text-xs px-2">✓</button>
                    </div>
                @endif
                <input wire:model="newProjectName" placeholder="Project name"
                       class="neo-input w-full text-sm" />
                <button wire:click="createProject"
                        class="neo-btn bg-black text-lime-400 w-full text-sm">Create Project</button>
            </div>
        </div>

        {{-- Tree --}}
        <div x-data="clientSorter($wire)" class="overflow-y-auto max-h-[70vh]">
            @forelse($clients as $client)
                <div data-client-id="{{ $client->id }}"
                     x-on:dragover.prevent
                     x-on:drop="dropOn($event, {{ $client->id }})"
                     x-bind:class="draggingClientId === {{ $client->id }} ? 'opacity-60 border-2 border-dashed border-black' : ''"
                     x-data="{ open: true }">
                    <div class="w-full px-3 py-2 border-b-2 border-black bg-lime-200 hover:bg-lime-300 flex items-center gap-2">
                        @if($manageMode && $editingClientId === $client->id)
                            <input wire:model="editingClientName" class="neo-input flex-1 text-sm py-1 px-2" />
                            <button wire:click="saveClientName" class="neo-btn bg-lime-400 text-black text-xs px-2 py-0.5">Save</button>
                            <button wire:click="cancelEditClient" class="neo-btn bg-white text-black text-xs px-2 py-0.5">Batal</button>
                        @else
                            <button @click="open = !open"
                                    draggable="true"
                                    x-on:dragstart="startDrag($event, {{ $client->id }})"
                                    x-on:dragend="endDrag()"
                                    class="flex-1 text-left font-black text-sm flex items-center justify-between">
                                <span>{{ $client->name }}</span>
                                <span x-text="open ? '▲' : '▼'" class="text-xs"></span>
                            </button>
                            @if($manageMode)
                                <button wire:click="startEditClient({{ $client->id }})" class="neo-btn bg-white text-black text-xs px-2 py-0.5">Edit</button>
                                <button wire:click="deleteClient({{ $client->id }})" wire:confirm="Hapus client ini beserta semua project dan task-nya?" class="neo-btn bg-red-400 text-white text-xs px-2 py-0.5">Hapus</button>
                            @endif
                        @endif
                    </div>
                    <div x-show="open" x-data="projectSorter({{ $client->id }}, $wire)" class="divide-y divide-gray-200">
                        @foreach($client->projects as $project)
                            <div data-sort-id="{{ $project->id }}"
                                 x-on:dragover.prevent
                                 x-on:drop="dropOn($event, {{ $project->id }})"
                                 x-on:dragend="endDrag()"
                                 x-bind:class="draggingId === {{ $project->id }} ? 'opacity-60 border-2 border-dashed border-black' : ''"
                                 class="px-3 py-2 text-sm font-bold hover:bg-lime-50 flex items-center gap-2 cursor-pointer {{ $selectedProjectId === $project->id ? 'bg-lime-400 border-l-4 border-black' : '' }}">
                                @if($manageMode && $editingProjectId === $project->id)
                                    <input wire:model="editingProjectName" class="neo-input flex-1 text-sm py-1 px-2" />
                                    <button wire:click="saveProjectName" class="neo-btn bg-lime-400 text-black text-xs px-2 py-0.5">Save</button>
                                    <button wire:click="cancelEditProject" class="neo-btn bg-white text-black text-xs px-2 py-0.5">Batal</button>
                                @else
                                    <button wire:click="selectProject({{ $project->id }})"
                                            draggable="true"
                                            x-on:dragstart="startDrag($event, {{ $project->id }})"
                                            class="flex-1 text-left truncate cursor-pointer select-none"
                                            title="Geser judul untuk ubah urutan">
                                        {{ $project->project_name }}
                                    </button>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <span class="text-xs font-bold text-gray-500">{{ $project->open_tasks_count }}</span>
                                        @if($manageMode)
                                            <button wire:click="startEditProject({{ $project->id }})" class="neo-btn bg-white text-black text-xs px-2 py-0.5">Edit</button>
                                            <button wire:click="deleteProject({{ $project->id }})" wire:confirm="Hapus project ini beserta semua task-nya?" class="neo-btn bg-red-400 text-white text-xs px-2 py-0.5">Hapus</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm font-bold text-gray-400">No clients yet.</div>
            @endforelse
        </div>
    </aside>

    <script>
        window.clientSorter = window.clientSorter || function clientSorter(wire) {
            return {
                draggingClientId: null,
                startDrag(event, clientId) {
                    this.draggingClientId = Number(clientId);
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', JSON.stringify({ type: 'client', clientId }));
                },
                endDrag() {
                    this.draggingClientId = null;
                },
                dropOn(event, targetClientId) {
                    event.preventDefault();
                    const raw = event.dataTransfer.getData('text/plain');
                    if (!raw) return;

                    let payload;
                    try {
                        payload = JSON.parse(raw);
                    } catch (_) {
                        return;
                    }

                    if (payload.type !== 'client') {
                        return;
                    }

                    const draggedId = Number(payload.clientId);
                    const targetId = Number(targetClientId);
                    if (!draggedId || !targetId || draggedId === targetId) {
                        this.draggingClientId = null;
                        return;
                    }

                    const ids = Array.from(event.currentTarget.parentElement.querySelectorAll(':scope > [data-client-id]'))
                        .map((el) => Number(el.dataset.clientId));

                    const from = ids.indexOf(draggedId);
                    const to = ids.indexOf(targetId);
                    if (from === -1 || to === -1) {
                        this.draggingClientId = null;
                        return;
                    }

                    ids.splice(from, 1);
                    ids.splice(to, 0, draggedId);
                    wire.reorderClients(ids);
                    this.draggingClientId = null;
                },
            };
        };

        window.projectSorter = window.projectSorter || function projectSorter(clientId, wire) {
            return {
                draggingId: null,
                startDrag(event, projectId) {
                    this.draggingId = Number(projectId);
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', JSON.stringify({ type: 'project', clientId, projectId }));
                },
                endDrag() {
                    this.draggingId = null;
                },
                dropOn(event, targetProjectId) {
                    event.preventDefault();
                    const raw = event.dataTransfer.getData('text/plain');
                    if (!raw) return;

                    let payload;
                    try {
                        payload = JSON.parse(raw);
                    } catch (_) {
                        return;
                    }

                    if (payload.type !== 'project') {
                        return;
                    }

                    if (Number(payload.clientId) !== Number(clientId)) {
                        return;
                    }

                    const draggedId = Number(payload.projectId);
                    const targetId = Number(targetProjectId);
                    if (!draggedId || !targetId || draggedId === targetId) {
                        this.draggingId = null;
                        return;
                    }

                    const ids = Array.from(event.currentTarget.parentElement.querySelectorAll(':scope > [data-sort-id]'))
                        .map((el) => Number(el.dataset.sortId));

                    const from = ids.indexOf(draggedId);
                    const to = ids.indexOf(targetId);
                    if (from === -1 || to === -1) {
                        this.draggingId = null;
                        return;
                    }

                    ids.splice(from, 1);
                    ids.splice(to, 0, draggedId);
                    wire.reorderProjects(clientId, ids);
                    this.draggingId = null;
                },
            };
        };

        window.taskSorter = window.taskSorter || function taskSorter(projectId, wire) {
            return {
                draggingId: null,
                startDrag(event, taskId) {
                    this.draggingId = Number(taskId);
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', JSON.stringify({ type: 'task', projectId, taskId }));
                },
                endDrag() {
                    this.draggingId = null;
                },
                dropOn(event, targetTaskId) {
                    event.preventDefault();
                    const raw = event.dataTransfer.getData('text/plain');
                    if (!raw) return;

                    let payload;
                    try {
                        payload = JSON.parse(raw);
                    } catch (_) {
                        return;
                    }

                    if (payload.type !== 'task') {
                        return;
                    }

                    if (Number(payload.projectId) !== Number(projectId)) {
                        return;
                    }

                    const draggedId = Number(payload.taskId);
                    const targetId = Number(targetTaskId);
                    if (!draggedId || !targetId || draggedId === targetId) {
                        this.draggingId = null;
                        return;
                    }

                    const ids = Array.from(event.currentTarget.parentElement.querySelectorAll(':scope > [data-task-id]'))
                        .map((el) => Number(el.dataset.taskId));

                    const from = ids.indexOf(draggedId);
                    const to = ids.indexOf(targetId);
                    if (from === -1 || to === -1) {
                        this.draggingId = null;
                        return;
                    }

                    ids.splice(from, 1);
                    ids.splice(to, 0, draggedId);
                    wire.reorderTasks(ids);
                    this.draggingId = null;
                },
            };
        };
    </script>

    {{-- Task List --}}
    <div class="flex-1 min-w-0">
        @if($viewMode === 'all')
            <div class="border-4 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                <div class="border-b-4 border-black bg-gradient-to-r from-lime-400 to-lime-300 px-4 py-3 space-y-3">
                    <h2 class="font-black text-lg uppercase">Semua Pekerjaan</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <input wire:model.live.debounce.300ms="allSearch"
                               type="text"
                               placeholder="Cari pekerjaan..."
                               class="neo-input w-full md:col-span-2 bg-white" />

                        <select wire:model.live="allClientId" class="neo-input w-full bg-white">
                            <option value="">Semua Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="allProjectId" class="neo-input w-full bg-white">
                            <option value="">Semua Project</option>
                            @foreach($allFilterProjects as $project)
                                <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button wire:click="resetAllFilters" class="neo-btn bg-white text-black text-sm px-3 py-1">Reset Filter</button>
                    </div>
                </div>

                <div class="divide-y-2 divide-black">
                    @forelse($tasks as $task)
                        @php
                            $overdue = $task->isOverdue();
                            $cl = app(\App\Services\ChecklistService::class)->parse($task->content ?? '');
                        @endphp
                        <div class="px-3 py-3 hover:bg-lime-50 transition-colors {{ $overdue ? 'border-l-4 border-red-500' : '' }}">
                            {{-- Row 1: dot + name --}}
                            <div class="flex items-center gap-2">
                                <span class="shrink-0 w-3 h-3 border-2 border-black {{ $task->status_color }}"></span>
                                <button wire:click="openTask({{ $task->id }})"
                                        class="flex-1 text-left font-bold hover:underline min-w-0 truncate">
                                    {{ $task->task_name }}
                                </button>
                            </div>
                            {{-- Row 2: project label + due date + status --}}
                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5 pl-5">
                                <span class="text-xs font-bold border-2 border-black px-1.5 py-0.5 bg-white truncate max-w-[160px]">
                                    {{ $task->project->client->name ?? '-' }} / {{ $task->project->project_name ?? '-' }}
                                </span>
                                @if($task->due_date)
                                    <span class="text-xs font-bold border-2 border-black px-1.5 py-0.5 shrink-0 {{ $overdue ? 'bg-red-400 text-white' : 'bg-gray-100' }}">
                                        {{ $task->due_date->format('d M') }}
                                    </span>
                                @endif
                                <select wire:change="updateTaskStatus({{ $task->id }}, $event.target.value)"
                                        class="text-xs border-2 border-black px-1.5 py-0.5 font-bold {{ $task->status_color }}">
                                    <option value="todo" @selected($task->status === 'todo')>To Do</option>
                                    <option value="in_progress" @selected($task->status === 'in_progress')>In Progress</option>
                                    <option value="done" @selected($task->status === 'done')>Done</option>
                                </select>
                            </div>

                            @if($cl['total'] > 0)
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="flex-1 h-1.5 bg-gray-200 border border-black">
                                        <div class="h-full bg-lime-500" style="width: {{ $cl['percent'] }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold">{{ $cl['completed'] }}/{{ $cl['total'] }}</span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center font-bold text-gray-400">Belum ada pekerjaan.</div>
                    @endforelse
                </div>

                <div class="px-4 py-3 border-t-4 border-black bg-lime-50 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-black uppercase">Rows</label>
                        <select wire:model.live="perPage" class="neo-input text-sm bg-white py-1 px-2 min-w-[88px]">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    {{ $tasks->links() }}
                </div>
            </div>
        @elseif($selectedProjectId)
            <div class="border-4 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                <div class="border-b-4 border-black bg-gradient-to-r from-lime-400 to-lime-300 px-4 py-3 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <button wire:click="$set('selectedProjectId', null)"
                                class="md:hidden neo-btn bg-white text-black text-xs px-2 py-1">← Back</button>
                        <h2 class="font-black text-lg uppercase">Tasks</h2>
                        <span x-show="positionSaved" x-transition.opacity class="text-[10px] font-black uppercase border-2 border-black bg-black text-lime-400 px-1.5 py-0.5">Urutan Tersimpan</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="togglePositionMode"
                                class="neo-btn text-xs {{ $positionMode ? 'bg-black text-lime-400' : 'bg-white text-black' }} px-2 py-1">📍 Posisi</button>
                        <button wire:click="$set('showCreateTask', true)"
                                class="neo-btn bg-black text-lime-400 text-sm px-3 py-1">+ Add Task</button>
                    </div>
                </div>

                {{-- New Task Form --}}
                @if($showCreateTask)
                    <div class="border-b-4 border-black p-4 bg-lime-50 space-y-3">
                        <input wire:model="taskName" placeholder="Task name *"
                               class="neo-input w-full" />
                        <div class="grid grid-cols-2 gap-3">
                            <select wire:model="taskStatus" class="neo-input">
                                <option value="todo">To Do</option>
                                <option value="in_progress">In Progress</option>
                                <option value="done">Done</option>
                            </select>
                            <input wire:model="taskDueDate" type="date" class="neo-input" />
                        </div>
                        <textarea wire:model="taskContent" rows="3"
                                  placeholder="Content... (supports #tags, - [ ] checklists)"
                                  class="neo-input w-full text-sm font-mono"></textarea>
                        <div class="flex gap-2">
                            <button wire:click="createTask"
                                    class="neo-btn bg-lime-400 border-black text-black font-bold px-4">Create</button>
                            <button wire:click="$set('showCreateTask', false)"
                                    class="neo-btn bg-white border-black font-bold px-4">Cancel</button>
                        </div>
                    </div>
                @endif

                {{-- Task Rows --}}
                <div class="divide-y-2 divide-black" x-data="taskSorter({{ $selectedProjectId }}, $wire)">
                    @forelse($tasks as $task)
                        @php
                            $overdue = $task->isOverdue();
                            $cl = app(\App\Services\ChecklistService::class)->parse($task->content ?? '');
                        @endphp
                        <div data-task-id="{{ $task->id }}"
                             x-on:dragover.prevent
                             x-on:drop="dropOn($event, {{ $task->id }})"
                             x-bind:class="draggingId === {{ $task->id }} ? 'opacity-60 border-2 border-dashed border-black' : ''"
                             class="px-3 py-3 hover:bg-lime-50 transition-colors {{ $overdue ? 'border-l-4 border-red-500' : '' }}">
                            {{-- Row 1: dot + drag handle (if positionMode) + name --}}
                            <div class="flex items-center gap-2">
                                @if($positionMode)
                                    <button draggable="true"
                                            x-on:dragstart="startDrag($event, {{ $task->id }})"
                                            x-on:dragend="endDrag()"
                                            title="Geser untuk ubah urutan"
                                            class="shrink-0 cursor-grab active:cursor-grabbing text-xl hover:bg-lime-200 px-1">⋮⋮</button>
                                @else
                                    <span class="shrink-0 w-3 h-3 border-2 border-black {{ $task->status_color }}"></span>
                                @endif
                                <button wire:click="openTask({{ $task->id }})"
                                        {{ $positionMode ? 'disabled' : '' }}
                                        class="flex-1 text-left font-bold hover:underline min-w-0 truncate {{ $positionMode ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    {{ $task->task_name }}
                                </button>
                            </div>
                            {{-- Row 2: move buttons + meta --}}
                            <div class="flex items-center gap-2 mt-1.5 pl-5">
                                @if(!$positionMode)
                                    <div class="flex gap-0.5 shrink-0">
                                        <button wire:click="moveTaskUp({{ $task->id }})"
                                                title="Geser ke atas"
                                                class="neo-btn bg-white text-black text-[10px] leading-none px-1.5 py-0.5 border border-black hover:bg-lime-200">▲</button>
                                        <button wire:click="moveTaskDown({{ $task->id }})"
                                                title="Geser ke bawah"
                                                class="neo-btn bg-white text-black text-[10px] leading-none px-1.5 py-0.5 border border-black hover:bg-lime-200">▼</button>
                                    </div>
                                @endif
                                @if($task->due_date)
                                    <span class="text-xs font-bold border-2 border-black px-1.5 py-0.5 shrink-0
                                        {{ $overdue ? 'bg-red-400 text-white' : 'bg-gray-100' }}">
                                        {{ $task->due_date->format('d M') }}
                                    </span>
                                @endif
                                <select wire:change="updateTaskStatus({{ $task->id }}, $event.target.value)" {{ $positionMode ? 'disabled' : '' }}
                                        class="text-xs border-2 border-black px-1.5 py-0.5 font-bold {{ $task->status_color }} min-w-0 {{ $positionMode ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    <option value="todo" @selected($task->status === 'todo')>To Do</option>
                                    <option value="in_progress" @selected($task->status === 'in_progress')>In Progress</option>
                                    <option value="done" @selected($task->status === 'done')>Done</option>
                                </select>
                            </div>

                            {{-- Checklist mini-progress --}}
                            @if($cl['total'] > 0)
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="flex-1 h-1.5 bg-gray-200 border border-black">
                                        <div class="h-full bg-lime-500" style="width: {{ $cl['percent'] }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold">{{ $cl['completed'] }}/{{ $cl['total'] }}</span>
                                </div>
                            @endif

                            {{-- Hashtags --}}
                            @if($task->hashtags->count())
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach($task->hashtags as $tag)
                                        <a href="{{ route('hashtags') }}?tag={{ $tag->tag_name }}"
                                           class="text-xs font-bold text-lime-700 hover:underline">#{{ $tag->tag_name }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center font-bold text-gray-400">No tasks yet. Add one above!</div>
                    @endforelse
                </div>

                <div class="px-4 py-3 border-t-4 border-black bg-lime-50 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-black uppercase">Rows</label>
                        <select wire:model.live="perPage" class="neo-input text-sm bg-white py-1 px-2 min-w-[88px]">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    {{ $tasks->links() }}
                </div>
            </div>

            {{-- Task Modal / Slide-over --}}
            @if($showTaskModal && $selectedTask)
                <div class="fixed inset-0 z-50 flex"
                     x-data x-on:keydown.escape.window="$wire.set('showTaskModal', false)">
                    {{-- Backdrop --}}
                    <div class="flex-1 bg-black/40" wire:click="$set('showTaskModal', false)"></div>

                    {{-- Panel --}}
                    <div class="w-full max-w-2xl bg-white border-l-4 border-black shadow-[-8px_0px_0px_0px_rgba(0,0,0,0.2)] overflow-y-auto flex flex-col">
                        <div class="border-b-4 border-black bg-lime-400 px-6 py-4 flex items-center justify-between">
                            <h3 class="font-black text-xl uppercase">Edit Task</h3>
                            <button wire:click="$set('showTaskModal', false)" class="neo-btn bg-white text-black px-3 py-1">✕</button>
                        </div>

                        <div class="p-6 flex-1 space-y-4">
                            <div>
                                <label class="neo-label">Task Name</label>
                                <input wire:model="taskName" class="neo-input w-full" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="neo-label">Status</label>
                                    <select wire:model="taskStatus" class="neo-input w-full">
                                        <option value="todo">To Do</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="done">Done</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="neo-label">Due Date</label>
                                    <input wire:model="taskDueDate" type="date" class="neo-input w-full" />
                                </div>
                            </div>

                            {{-- Notion-style content editor --}}
                            <div>
                                <label class="neo-label">Content <span class="text-gray-400 font-normal text-xs">(markdown: #tags, - [ ] checklists, links)</span></label>
                                <textarea wire:model="taskContent" rows="12"
                                          class="neo-input w-full font-mono text-sm"
                                          placeholder="Write content here...&#10;&#10;Use:&#10;  #hashtag — auto-tag&#10;  - [ ] todo item&#10;  - [x] done item&#10;  https://link.com"></textarea>
                            </div>

                            {{-- Checklist Preview --}}
                            @if($checklist['total'] > 0)
                                <div class="border-4 border-black p-4 bg-lime-50">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="font-black text-sm uppercase">Checklist</span>
                                        <span class="font-bold text-sm">{{ $checklist['completed'] }}/{{ $checklist['total'] }} ({{ $checklist['percent'] }}%)</span>
                                    </div>
                                    <div class="h-2 bg-gray-200 border border-black mb-3">
                                        <div class="h-full bg-lime-500 transition-all" style="width: {{ $checklist['percent'] }}%"></div>
                                    </div>
                                    @foreach($checklist['items'] as $i => $item)
                                        <div class="flex items-center gap-2 py-1">
                                            <button wire:click="toggleChecklist({{ $selectedTask->id }}, {{ $i }})"
                                                    class="w-5 h-5 border-2 border-black shrink-0 flex items-center justify-center
                                                        {{ $item['checked'] ? 'bg-lime-400' : 'bg-white' }}">
                                                @if($item['checked']) ✓ @endif
                                            </button>
                                            <span class="text-sm font-bold {{ $item['checked'] ? 'line-through text-gray-400' : '' }}">
                                                {{ $item['text'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="border-t-4 border-black px-6 py-4 flex items-center justify-between bg-gray-50">
                            <button wire:click="deleteTask({{ $selectedTask->id }})"
                                    wire:confirm="Delete this task?"
                                    class="neo-btn bg-red-400 border-black text-white font-bold px-4 py-2">
                                🗑 Delete
                            </button>
                            <button wire:click="saveTask"
                                    class="neo-btn bg-lime-400 border-black text-black font-black px-6 py-2">
                                💾 Save
                            </button>
                        </div>
                    </div>
                </div>
            @endif

        @else
            <div class="border-4 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] p-12 text-center hidden md:block">
                <p class="text-4xl mb-4">👈</p>
                <p class="font-black text-xl">Select a project to view tasks</p>
            </div>
        @endif
    </div>
</div>
</div>
