<div class="space-y-8">

    {{-- Page Title --}}
    <div class="flex items-center gap-3">
        <h1 class="text-3xl font-black uppercase tracking-tight">Dashboard</h1>
        <span class="bg-lime-400 border-2 border-black px-2 py-0.5 text-sm font-bold">Overview</span>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- To Do --}}
        <div class="border-4 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] p-4">
            <p class="text-xs font-bold uppercase text-gray-500 mb-1">To Do</p>
            <p class="text-4xl font-black">{{ $stats['todo'] }}</p>
            <span class="inline-block mt-2 bg-gray-100 border-2 border-black px-2 py-0.5 text-xs font-bold">📋 Pending</span>
        </div>
        {{-- In Progress --}}
        <div class="border-4 border-black bg-yellow-300 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] p-4">
            <p class="text-xs font-bold uppercase text-gray-700 mb-1">In Progress</p>
            <p class="text-4xl font-black">{{ $stats['in_progress'] }}</p>
            <span class="inline-block mt-2 bg-yellow-100 border-2 border-black px-2 py-0.5 text-xs font-bold">⚡ Active</span>
        </div>
        {{-- Done --}}
        <div class="border-4 border-black bg-gradient-to-br from-lime-400 to-lime-500 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] p-4">
            <p class="text-xs font-bold uppercase mb-1">Done</p>
            <p class="text-4xl font-black">{{ $stats['done'] }}</p>
            <span class="inline-block mt-2 bg-lime-200 border-2 border-black px-2 py-0.5 text-xs font-bold">✅ Complete</span>
        </div>
        {{-- Overdue --}}
        <div class="border-4 border-black bg-gradient-to-br from-orange-400 to-red-400 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] p-4">
            <p class="text-xs font-bold uppercase mb-1">Overdue</p>
            <p class="text-4xl font-black">{{ $stats['overdue'] }}</p>
            <span class="inline-block mt-2 bg-red-100 border-2 border-black px-2 py-0.5 text-xs font-bold">🔥 Urgent</span>
        </div>
    </div>

    {{-- Main Section --}}
    <div class="grid md:grid-cols-2 gap-6">

        {{-- Left: Active Projects --}}
        <div>
            <div class="border-4 border-black bg-gradient-to-b from-lime-100 to-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                <div class="border-b-4 border-black bg-lime-400 px-4 py-3">
                    <h2 class="font-black text-lg uppercase">🗂️ Active Projects</h2>
                </div>
                <div class="divide-y-2 divide-black">
                    @forelse($activeProjects as $project)
                        <div class="px-4 py-3 hover:bg-lime-50 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-bold text-sm">{{ $project->client->name ?? '—' }}</p>
                                    <a href="{{ route('projects') }}?selectedProjectId={{ $project->id }}"
                                       class="font-black hover:underline">
                                        {{ $project->project_name }}
                                    </a>
                                </div>
                                <span class="shrink-0 text-xs font-bold border-2 border-black px-2 py-0.5 bg-white">
                                    {{ $project->tasks_count }} tasks
                                </span>
                            </div>
                            {{-- Mini progress bar --}}
                            @php
                                $total = $project->tasks_count;
                                $done  = $project->done_count;
                                $pct   = $total > 0 ? round(($done/$total)*100) : 0;
                            @endphp
                            <div class="mt-2 h-2 bg-gray-200 border border-black">
                                <div class="h-full bg-lime-500" style="width: {{ $pct }}%"></div>
                            </div>
                            <div class="flex justify-between mt-1 text-xs font-bold text-gray-500">
                                <span>{{ $project->todo_count }} todo · {{ $project->in_progress_count }} active</span>
                                <span>{{ $pct }}%</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center font-bold text-gray-400">No active projects yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: Urgent Tasks --}}
        <div>
            <div class="border-4 border-black bg-gradient-to-b from-orange-100 to-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                <div class="border-b-4 border-black bg-orange-400 px-4 py-3">
                    <h2 class="font-black text-lg uppercase">🚨 Top 5 Urgent Tasks</h2>
                </div>
                <div class="divide-y-2 divide-black">
                    @forelse($urgentTasks as $task)
                        @php $overdue = $task->isOverdue(); @endphp
                        <div class="px-4 py-3 {{ $overdue ? 'bg-red-50' : '' }} hover:bg-orange-50 transition-colors">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-gray-500 truncate">
                                        {{ $task->project->client->name ?? '' }} / {{ $task->project->project_name }}
                                    </p>
                                    <p class="font-black truncate">{{ $task->task_name }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="block text-xs font-bold border-2 border-black px-2 py-0.5
                                        {{ $overdue ? 'bg-red-400 text-white' : 'bg-yellow-300' }}">
                                        {{ $task->due_date->format('d M') }}
                                    </span>
                                    @if ($overdue)
                                        <span class="text-xs font-bold text-red-600 mt-0.5 block">OVERDUE</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-1">
                                <span class="text-xs font-bold border border-black px-1.5 py-0.5 {{ $task->status_color }}">
                                    {{ $task->status_label }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center font-bold text-gray-400">No urgent tasks 🎉</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
