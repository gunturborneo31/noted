<div class="flex gap-0 min-h-[80vh]">

    {{-- Sidebar: Note List --}}
    <aside class="w-full md:w-64 shrink-0 border-4 border-black bg-gradient-to-b from-lime-50 to-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] md:mr-6 mb-6 md:mb-0 self-start">
        <div class="border-b-4 border-black bg-lime-400 px-4 py-3 flex items-center justify-between">
            <span class="font-black uppercase text-sm">Notes</span>
            <button wire:click="$set('showNewForm', true)"
                    class="neo-btn bg-black text-lime-400 px-2 py-0.5 text-xs">+ New</button>
        </div>

        {{-- New Note Form --}}
        @if($showNewForm)
            <div class="border-b-4 border-black p-3 bg-lime-100 space-y-2">
                <input wire:model="newTitle" placeholder="Note title *"
                       class="neo-input w-full text-sm" />
                <div class="flex gap-2">
                    <button wire:click="createNote"
                            class="neo-btn bg-lime-400 border-black text-black text-xs px-3 py-1">Create</button>
                    <button wire:click="$set('showNewForm', false)"
                            class="neo-btn bg-white border-black text-xs px-3 py-1">Cancel</button>
                </div>
            </div>
        @endif

        {{-- Note List --}}
        <div x-data="noteSorter($wire)" class="overflow-y-auto max-h-[75vh] divide-y-2 divide-black">
            @forelse($notes as $note)
                <div data-sort-id="{{ $note->id }}"
                     x-on:dragover.prevent
                     x-on:drop="dropOn($event, {{ $note->id }})"
                     class="px-3 py-2 hover:bg-lime-50 transition-colors flex items-center gap-2 {{ $noteId === $note->id ? 'bg-lime-400 border-l-4 border-black' : '' }}">
                    <button wire:click="selectNote({{ $note->id }})"
                            draggable="true"
                            x-on:dragstart="startDrag($event, {{ $note->id }})"
                            class="flex-1 text-left">
                        <p class="font-bold text-sm truncate">{{ $note->title }}</p>
                        <p class="text-xs text-gray-500">{{ $note->updated_at->diffForHumans() }}</p>
                    </button>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm font-bold text-gray-400">No notes yet.</div>
            @endforelse
        </div>
    </aside>

    <script>
        window.noteSorter = window.noteSorter || function noteSorter(wire) {
            return {
                startDrag(event, noteId) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', String(noteId));
                },
                dropOn(event, targetNoteId) {
                    const raw = event.dataTransfer.getData('text/plain');
                    const draggedId = Number(raw);
                    const targetId = Number(targetNoteId);

                    if (!draggedId || !targetId || draggedId === targetId) {
                        return;
                    }

                    const ids = Array.from(event.currentTarget.parentElement.querySelectorAll('[data-sort-id]'))
                        .map((el) => Number(el.dataset.sortId));

                    const from = ids.indexOf(draggedId);
                    const to = ids.indexOf(targetId);
                    if (from === -1 || to === -1) {
                        return;
                    }

                    ids.splice(from, 1);
                    ids.splice(to, 0, draggedId);
                    wire.reorderNotes(ids);
                },
            };
        };
    </script>

    {{-- Note Editor --}}
    <div class="flex-1 min-w-0">
        @if($currentNote)
            <div class="border-4 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">

                {{-- Header --}}
                <div class="border-b-4 border-black bg-gradient-to-r from-lime-400 to-lime-300 px-6 py-3 flex items-center justify-between">
                    @if($isEditing)
                        <input wire:model="title"
                               class="neo-input font-black text-xl flex-1 mr-4 bg-white" />
                    @else
                        <h2 class="font-black text-xl">{{ $currentNote->title }}</h2>
                    @endif

                    <div class="flex gap-2">
                        @if($isEditing)
                            <button wire:click="saveNote"
                                    class="neo-btn bg-black text-lime-400 font-bold px-3 py-1 text-sm">💾 Save</button>
                            <button wire:click="cancelEdit"
                                    class="neo-btn bg-white border-black text-sm px-3 py-1">Cancel</button>
                        @else
                            <button wire:click="startEdit"
                                    class="neo-btn bg-white border-black font-bold px-3 py-1 text-sm">✏️ Edit</button>
                            <button wire:click="deleteNote({{ $currentNote->id }})"
                                    wire:confirm="Delete this note permanently?"
                                    class="neo-btn bg-red-400 border-black text-white font-bold px-3 py-1 text-sm">🗑</button>
                        @endif
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-6 space-y-6">

                    @if($isEditing)
                        {{-- Rich Editor Instructions --}}
                        <div class="border-2 border-black bg-lime-50 p-3 text-xs font-mono space-y-1">
                            <p class="font-bold">Supported syntax:</p>
                            <p><code>#hashtag</code> — auto-tag on save</p>
                            <p><code>- [ ] task</code> / <code>- [x] done</code> — checklists</p>
                            <p><code>**bold**</code> <code>*italic*</code> <code>`code`</code></p>
                            <p><code>![alt](url)</code> — images &nbsp; <code>[text](url)</code> — links</p>
                            <p><code>https://youtube.com/embed/ID</code> — video embed</p>
                        </div>
                        <textarea wire:model="body" rows="20"
                                  class="neo-input w-full font-mono text-sm"
                                  placeholder="Write your note here..."></textarea>
                    @else
                        {{-- Rendered Preview --}}
                        <div class="prose prose-sm max-w-none font-mono note-body">
                            @php
                                $rendered = \App\Services\NoteRenderer::render($currentNote->body ?? '');
                            @endphp
                            {!! $rendered !!}
                        </div>
                    @endif

                    {{-- Hashtags --}}
                    @if($currentNote->hashtags->count())
                        <div class="flex flex-wrap gap-2 pt-2 border-t-2 border-black">
                            @foreach($currentNote->hashtags as $tag)
                                <a href="{{ route('hashtags') }}?tag={{ $tag->tag_name }}"
                                   class="border-2 border-black bg-lime-300 px-2 py-0.5 text-sm font-bold hover:bg-lime-400 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                                    #{{ $tag->tag_name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Credentials Section --}}
                    <div class="border-4 border-black bg-gradient-to-b from-gray-50 to-white">
                        <div class="border-b-4 border-black bg-gray-800 text-white px-4 py-2 flex items-center justify-between">
                            <span class="font-black uppercase text-sm">🔐 Saved Credentials</span>
                            <div class="flex gap-2">
                                <button wire:click="$toggle('showPasswords')"
                                        class="neo-btn bg-gray-600 text-white text-xs px-2 py-0.5 border-gray-400">
                                    {{ $showPasswords ? '🙈 Hide' : '👁 Show' }} Passwords
                                </button>
                                <button wire:click="$set('showCredentialForm', true)"
                                        class="neo-btn bg-lime-400 text-black text-xs px-2 py-0.5 border-black">+ Add</button>
                            </div>
                        </div>

                        {{-- Credential Form --}}
                        @if($showCredentialForm)
                            <div class="border-b-4 border-black p-4 bg-gray-100 space-y-3">
                                <div>
                                    <label class="neo-label">URL / Login Page</label>
                                    <input wire:model="credUrlLogin" type="url" placeholder="https://example.com/login"
                                           class="neo-input w-full text-sm" />
                                    @error('credUrlLogin') <p class="text-red-600 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="neo-label">Username / Email</label>
                                        <input wire:model="credUsername" class="neo-input w-full text-sm" />
                                    </div>
                                    <div>
                                        <label class="neo-label">Password *</label>
                                        <input wire:model="credPassword" type="password" class="neo-input w-full text-sm" />
                                        @error('credPassword') <p class="text-red-600 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="saveCredential"
                                            class="neo-btn bg-lime-400 border-black text-black font-bold px-4 py-1 text-sm">
                                        {{ $editingCredentialId ? 'Update' : 'Save' }}
                                    </button>
                                    <button wire:click="$set('showCredentialForm', false)"
                                            class="neo-btn bg-white border-black font-bold px-4 py-1 text-sm">Cancel</button>
                                </div>
                            </div>
                        @endif

                        {{-- Credential List --}}
                        <div class="divide-y-2 divide-gray-300">
                            @forelse($credentials as $cred)
                                <div class="px-4 py-3 flex items-center gap-4">
                                    <div class="flex-1 min-w-0">
                                        @if($cred->url_login)
                                            <a href="{{ $cred->url_login }}" target="_blank"
                                               class="text-xs font-bold text-blue-700 hover:underline truncate block">
                                                🔗 {{ $cred->url_login }}
                                            </a>
                                        @endif
                                        <p class="font-bold text-sm">{{ $cred->username ?? '—' }}</p>
                                        <p class="font-mono text-sm">
                                            {{ $showPasswords ? $cred->password : str_repeat('•', 12) }}
                                        </p>
                                    </div>
                                    <div class="flex gap-1 shrink-0">
                                        <button wire:click="editCredential({{ $cred->id }})"
                                                class="neo-btn bg-yellow-300 border-black text-xs px-2 py-1">Edit</button>
                                        <button wire:click="deleteCredential({{ $cred->id }})"
                                                wire:confirm="Delete this credential?"
                                                class="neo-btn bg-red-400 border-black text-white text-xs px-2 py-1">✕</button>
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-6 text-center text-sm font-bold text-gray-400">No credentials saved.</div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        @else
            <div class="border-4 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] p-12 text-center">
                <p class="text-4xl mb-4">📝</p>
                <p class="font-black text-xl">Select or create a note</p>
            </div>
        @endif
    </div>
</div>
