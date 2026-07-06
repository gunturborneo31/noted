<div class="space-y-4">
    <div class="neo-card p-4 bg-lime-50">
        <h2 class="font-black text-lg uppercase">Folder Akun</h2>
        <p class="text-xs font-bold text-gray-600">Contoh: Mahulu -> Bappelitbangda -> IG/YouTube/Email</p>

        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-2">
            <input wire:model="folderName" type="text" placeholder="Nama folder"
                   class="neo-input w-full" />
            <select wire:model="parentFolderId" class="neo-input w-full">
                <option value="">Folder Induk (opsional)</option>
                @foreach($allFolders as $folder)
                    <option value="{{ $folder->id }}">{{ $folder->full_path }}</option>
                @endforeach
            </select>
            <button wire:click="createFolder" class="neo-btn bg-black text-lime-400">+ Buat Folder/Subfolder</button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="neo-card p-4">
            <h3 class="font-black uppercase mb-3">Struktur Folder</h3>
            <div x-data="folderSorter(null, $wire)" class="space-y-2 max-h-[60vh] overflow-y-auto">
                @forelse($folderTree as $root)
                    @include('livewire.partials.account-folder-tree', ['folder' => $root, 'depth' => 0, 'selectedFolderId' => $selectedFolderId])
                @empty
                    <p class="text-sm text-gray-500 font-bold">Belum ada folder.</p>
                @endforelse
            </div>
        </div>

        <div class="neo-card p-4 lg:col-span-2 space-y-4">
            <div>
                <h3 class="font-black uppercase">Isi Folder</h3>
                @if($selectedFolder)
                    <p class="text-sm font-bold text-gray-700">{{ $selectedFolder->full_path }}</p>
                @else
                    <p class="text-sm font-bold text-gray-500">Pilih folder untuk isi akun.</p>
                @endif
            </div>

            <div class="border-2 border-black p-3 bg-lime-50">
                <label class="neo-label">Tambah beberapa akun sekaligus</label>
                <datalist id="platform-options">
                    <option value="IG"></option>
                    <option value="YouTube"></option>
                    <option value="Email"></option>
                    <option value="TikTok"></option>
                    <option value="Facebook"></option>
                    <option value="X"></option>
                    <option value="Website"></option>
                </datalist>

                <div class="space-y-2">
                    @foreach($accountRows as $i => $row)
                        <div wire:key="account-row-{{ $i }}"
                                x-data="{ loginType: '{{ $accountRows[$i]['login_type'] ?? 'credentials' }}' }"
                             class="grid grid-cols-1 md:grid-cols-12 gap-2 border-2 border-black p-2 bg-white">
                            <div class="md:col-span-3">
                                <label class="neo-label">Platform</label>
                                <input type="text"
                                       list="platform-options"
                                       wire:model="accountRows.{{ $i }}.platform"
                                       class="neo-input w-full"
                                       placeholder="Pilih/ketik platform" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="neo-label">Status Login</label>
                                <select x-model="loginType" wire:model.live="accountRows.{{ $i }}.login_type" class="neo-input w-full">
                                    <option value="credentials">Username/Password</option>
                                    <option value="google">Google</option>
                                    <option value="email">Email</option>
                                </select>
                            </div>

                            <div x-show="loginType === 'credentials'" class="md:col-span-3">
                                <label class="neo-label">Username</label>
                                <input type="text"
                                       wire:model="accountRows.{{ $i }}.username"
                                       class="neo-input w-full"
                                       placeholder="username / email" />
                            </div>

                            <div x-show="loginType === 'credentials'" class="md:col-span-3">
                                <label class="neo-label">Password</label>
                                <input type="text"
                                       wire:model="accountRows.{{ $i }}.password"
                                       class="neo-input w-full"
                                       placeholder="password" />
                            </div>

                            <div class="md:col-span-1 flex items-end">
                                <button wire:click="removeRow({{ $i }})"
                                        class="neo-btn bg-red-400 text-white w-full"
                                        @disabled(count($accountRows) === 1)>
                                    -
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2 flex flex-wrap gap-2">
                    <button wire:click="addRow" class="neo-btn bg-white text-black">+ Tambah Baris</button>
                    <button wire:click="saveAccounts" class="neo-btn bg-lime-400 text-black">Simpan Beberapa Akun</button>
                </div>
            </div>

            <div class="space-y-2">
                @if($selectedFolder && $selectedFolder->accounts->count())
                    @foreach($selectedFolder->accounts as $account)
                        <div class="border-2 border-black p-3 flex items-center justify-between gap-3">
                            <div class="flex-1">
                                @if($editingAccountId === $account->id)
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2" x-data="{ editType: @entangle('editLoginType').live }">
                                        <input wire:model="editPlatform" class="neo-input" placeholder="Platform" />

                                        <select wire:model.live="editLoginType" x-model="editType" class="neo-input">
                                            <option value="credentials">Username/Password</option>
                                            <option value="google">Google</option>
                                            <option value="email">Email</option>
                                        </select>

                                        <input x-show="editType === 'credentials'" wire:model="editUsername" class="neo-input" placeholder="Username" />
                                        <input x-show="editType === 'credentials'" wire:model="editPassword" class="neo-input" placeholder="Password" />
                                    </div>
                                @else
                                    <p class="font-black text-sm uppercase">{{ $account->platform }}</p>
                                    @if(($account->login_type ?? 'credentials') === 'credentials')
                                        <p class="text-sm font-bold">Username: {{ $account->username ?: $account->account_value }}</p>
                                        <p class="text-sm font-bold">Password: {{ $account->password ?: '-' }}</p>
                                    @else
                                        <p class="text-sm font-bold">Status login: Masuk dengan {{ ucfirst($account->login_type) }}</p>
                                    @endif
                                @endif
                            </div>

                            <div class="flex gap-2 items-center shrink-0">
                                @if($editingAccountId === $account->id)
                                    <button wire:click="saveEditAccount" class="neo-btn bg-lime-400 text-black text-xs px-2 py-1">Simpan</button>
                                    <button wire:click="cancelEditAccount" class="neo-btn bg-white text-black text-xs px-2 py-1">Batal</button>
                                @else
                                    <button wire:click="startEditAccount({{ $account->id }})" class="neo-btn bg-white text-black text-xs px-2 py-1">Edit</button>
                                    @php
                                        $copyText = ($account->login_type ?? 'credentials') === 'credentials'
                                            ? ("Platform: {$account->platform}\nUsername: ".($account->username ?: $account->account_value)."\nPassword: ".($account->password ?: '-'))
                                            : ("Platform: {$account->platform}\nStatus: Masuk dengan ".ucfirst($account->login_type));
                                    @endphp
                                    <button
                                        type="button"
                                        data-copy="{{ $copyText }}"
                                        onclick="event.preventDefault(); event.stopPropagation(); const t = this.dataset.copy; if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t); } else { const ta = document.createElement('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }"
                                        class="neo-btn bg-lime-300 text-black text-xs px-2 py-1">
                                        Copy
                                    </button>
                                @endif
                                <button wire:click="deleteAccount({{ $account->id }})" class="neo-btn bg-red-400 text-white text-xs px-2 py-1">Hapus</button>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-sm font-bold text-gray-500">Belum ada akun di folder ini.</p>
                @endif
            </div>
        </div>
    </div>

</div>

<script>
    window.folderSorter = window.folderSorter || function folderSorter(parentId, wire) {
        const normalizedParent = parentId === null || parentId === '' ? null : Number(parentId);

        return {
            startDrag(event, folderId) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', JSON.stringify({ parentId: normalizedParent, folderId }));
            },
            dropOn(event, targetFolderId) {
                const raw = event.dataTransfer.getData('text/plain');
                if (!raw) return;

                let payload;
                try {
                    payload = JSON.parse(raw);
                } catch (_) {
                    return;
                }

                const payloadParent = payload.parentId === null || payload.parentId === '' ? null : Number(payload.parentId);
                if (payloadParent !== normalizedParent) {
                    return;
                }

                const draggedId = Number(payload.folderId);
                const targetId = Number(targetFolderId);
                if (!draggedId || !targetId || draggedId === targetId) {
                    return;
                }

                const ids = Array.from(event.currentTarget.parentElement.querySelectorAll(':scope > [data-folder-id]'))
                    .map((el) => Number(el.dataset.folderId));

                const from = ids.indexOf(draggedId);
                const to = ids.indexOf(targetId);
                if (from === -1 || to === -1) {
                    return;
                }

                ids.splice(from, 1);
                ids.splice(to, 0, draggedId);
                wire.reorderFolders(normalizedParent, ids);
            },
        };
    };
</script>
