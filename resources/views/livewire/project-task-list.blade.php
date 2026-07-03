<div class="flex gap-0 min-h-[80vh]" x-data="{}">

    {{-- Sidebar: Client → Project Tree --}}
    <aside class="w-full md:w-64 shrink-0 border-4 border-black bg-gradient-to-b from-lime-50 to-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] md:mr-6 mb-6 md:mb-0 self-start">
        <div class="border-b-4 border-black bg-lime-400 px-4 py-3 flex items-center justify-between">
            <span class="font-black uppercase text-sm">Projects</span>
            <button wire:click="$set('showCreateTask', false)"
                    x-data x-on:click="$dispatch('open-new-project')"
                    class="neo-btn bg-black text-lime-400 px-2 py-0.5 text-xs">+ New</button>
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
        <div class="overflow-y-auto max-h-[70vh]">
            @forelse($clients as $client)
                <div x-data="{ open: true }">
                    <button @click="open = !open"
                            class="w-full text-left px-3 py-2 font-black text-sm border-b-2 border-black bg-lime-200 hover:bg-lime-300 flex items-center justify-between">
                        <span>{{ $client->name }}</span>
                        <span x-text="open ? '▲' : '▼'" class="text-xs"></span>
                    </button>
                    <div x-show="open" class="divide-y divide-gray-200">
                        @foreach($client->projects as $project)
                            <button wire:click="selectProject({{ $project->id }})"
                                    class="w-full text-left px-4 py-2 text-sm font-bold hover:bg-lime-50 flex items-center justify-between gap-1
                                        {{ $selectedProjectId === $project->id ? 'bg-lime-400 border-l-4 border-black' : '' }}">
                                <span class="truncate">{{ $project->project_name }}</span>
                                <span class="text-xs font-bold text-gray-500 shrink-0">{{ $project->tasks_count }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm font-bold text-gray-400">No clients yet.</div>
            @endforelse
        </div>
    </aside>

    {{-- Task List --}}
    <div class="flex-1 min-w-0">
        @if($selectedProjectId)
            <div class="border-4 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                <div class="border-b-4 border-black bg-gradient-to-r from-lime-400 to-lime-300 px-4 py-3 flex items-center justify-between">
                    <h2 class="font-black text-lg uppercase">Tasks</h2>
                    <button wire:click="$set('showCreateTask', true)"
                            class="neo-btn bg-black text-lime-400 text-sm px-3 py-1">+ Add Task</button>
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
                <div class="divide-y-2 divide-black">
                    @forelse($tasks as $task)
                        @php
                            $overdue = $task->isOverdue();
                            $cl = app(\App\Services\ChecklistService::class)->parse($task->content ?? '');
                        @endphp
                        <div class="px-4 py-3 hover:bg-lime-50 transition-colors {{ $overdue ? 'border-l-4 border-red-500' : '' }}">
                            <div class="flex items-center gap-3">
                                {{-- Status dot --}}
                                <span class="shrink-0 w-3 h-3 border-2 border-black {{ $task->status_color }}"></span>

                                <button wire:click="openTask({{ $task->id }})"
                                        class="flex-1 text-left font-bold hover:underline truncate">
                                    {{ $task->task_name }}
                                </button>

                                <div class="flex items-center gap-2 shrink-0">
                                    @if($task->due_date)
                                        <span class="text-xs font-bold border-2 border-black px-1.5 py-0.5
                                            {{ $overdue ? 'bg-red-400 text-white' : 'bg-gray-100' }}">
                                            {{ $task->due_date->format('d M') }}
                                        </span>
                                    @endif
                                    <span class="text-xs border-2 border-black px-1.5 py-0.5 font-bold {{ $task->status_color }}">
                                        {{ $task->status_label }}
                                    </span>
                                </div>
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
            <div class="border-4 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] p-12 text-center">
                <p class="text-4xl mb-4">👈</p>
                <p class="font-black text-xl">Select a project to view tasks</p>
            </div>
        @endif
    </div>
</div>
