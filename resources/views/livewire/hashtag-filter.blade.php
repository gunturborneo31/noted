<div class="space-y-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 flex-wrap">
        <h1 class="text-3xl font-black uppercase tracking-tight">#Hashtag Filter</h1>
        @if($hashtag)
            <span class="border-4 border-black bg-lime-400 px-3 py-1 font-black text-lg shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                #{{ $hashtag->tag_name }}
            </span>
        @endif
    </div>

    {{-- Search Bar --}}
    <form wire:submit="applySearch" class="flex gap-0">
        <span class="border-4 border-r-0 border-black bg-lime-400 px-4 flex items-center font-black text-xl">#</span>
        <input wire:model="search"
               placeholder="Search hashtag..."
               class="border-4 border-black px-4 py-3 font-bold text-lg flex-1 outline-none focus:bg-lime-50" />
        <button type="submit"
                class="neo-btn bg-black text-lime-400 font-black px-6 py-3 text-lg">Search</button>
    </form>

    {{-- Popular Tags Cloud --}}
    <div>
        <p class="font-black text-sm uppercase mb-3">Popular Tags</p>
        <div class="flex flex-wrap gap-2">
            @foreach($popularTags as $pt)
                <button wire:click="filterByTag('{{ $pt->tag_name }}')"
                        class="border-2 border-black px-3 py-1 font-bold text-sm hover:bg-lime-400
                            shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none
                            transition-transform
                            {{ $tag === $pt->tag_name ? 'bg-lime-400' : 'bg-lime-100' }}">
                    #{{ $pt->tag_name }}
                    <span class="ml-1 text-xs opacity-60">{{ $pt->tasks_count + $pt->notes_count }}</span>
                </button>
            @endforeach
            @if($popularTags->isEmpty())
                <p class="text-sm font-bold text-gray-400">No tags yet. Start adding #hashtags in tasks or notes!</p>
            @endif
        </div>
    </div>

    @if($hashtag)
        <div class="grid md:grid-cols-2 gap-6">

            {{-- Tasks --}}
            <div class="border-4 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                <div class="border-b-4 border-black bg-gradient-to-r from-lime-400 to-lime-300 px-4 py-3">
                    <h2 class="font-black text-lg uppercase">📋 Tasks ({{ $tasks->count() }})</h2>
                </div>
                <div class="divide-y-2 divide-black bg-white">
                    @forelse($tasks as $task)
                        @php $overdue = $task->isOverdue(); @endphp
                        <div class="px-4 py-3 {{ $overdue ? 'bg-red-50' : 'hover:bg-lime-50' }}">
                            <p class="text-xs font-bold text-gray-500">
                                {{ $task->project->client->name ?? '' }} / {{ $task->project->project_name }}
                            </p>
                            <div class="flex items-center justify-between gap-2 mt-0.5">
                                <a href="{{ route('projects') }}?selectedProjectId={{ $task->project_id }}"
                                   class="font-black hover:underline flex-1 truncate">
                                    {{ $task->task_name }}
                                </a>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="text-xs font-bold border-2 border-black px-1.5 py-0.5 {{ $task->status_color }}">
                                        {{ $task->status_label }}
                                    </span>
                                    @if($task->due_date)
                                        <span class="text-xs font-bold border-2 border-black px-1.5 py-0.5
                                            {{ $overdue ? 'bg-red-400 text-white' : 'bg-gray-100' }}">
                                            {{ $task->due_date->format('d M') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center font-bold text-gray-400">No tasks with #{{ $hashtag->tag_name }}</div>
                    @endforelse
                </div>
            </div>

            {{-- Notes --}}
            <div class="border-4 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                <div class="border-b-4 border-black bg-gradient-to-r from-yellow-300 to-lime-300 px-4 py-3">
                    <h2 class="font-black text-lg uppercase">📝 Notes ({{ $notes->count() }})</h2>
                </div>
                <div class="divide-y-2 divide-black bg-white">
                    @forelse($notes as $note)
                        <div class="px-4 py-3 hover:bg-yellow-50">
                            <a href="{{ route('notes') }}?noteId={{ $note->id }}"
                               class="font-black hover:underline block">{{ $note->title }}</a>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $note->updated_at->diffForHumans() }}</p>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center font-bold text-gray-400">No notes with #{{ $hashtag->tag_name }}</div>
                    @endforelse
                </div>
            </div>

        </div>
    @elseif($search)
        <div class="border-4 border-black bg-white p-8 text-center shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <p class="font-black text-xl">Tag <span class="text-lime-600">#{{ $search }}</span> not found.</p>
        </div>
    @endif
</div>
