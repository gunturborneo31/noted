<div data-folder-id="{{ $folder->id }}" class="space-y-1" style="margin-left: {{ $depth * 14 }}px;" x-on:dragover.prevent x-on:drop="dropOn($event, {{ $folder->id }})">
    <div class="flex items-center gap-1">
        <button wire:click="selectFolder({{ $folder->id }})"
                draggable="true"
                x-on:dragstart="startDrag($event, {{ $folder->id }})"
                class="flex-1 text-left border-2 border-black px-2 py-1 font-bold text-sm {{ $selectedFolderId === $folder->id ? 'bg-lime-400' : 'bg-white hover:bg-lime-50' }}">
            {{ $folder->name }}
            <span class="text-xs text-gray-600">({{ $folder->accounts->count() }})</span>
        </button>
    </div>

    @if($folder->children->count())
        <div x-data="folderSorter({{ $folder->id }}, $wire)" class="space-y-1">
            @foreach($folder->children as $child)
                @include('livewire.partials.account-folder-tree', ['folder' => $child, 'depth' => $depth + 1, 'selectedFolderId' => $selectedFolderId])
            @endforeach
        </div>
    @endif
</div>
