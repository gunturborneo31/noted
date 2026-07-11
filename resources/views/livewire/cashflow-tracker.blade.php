<div class="space-y-4">
    <div class="neo-card p-3 flex items-center gap-3 bg-lime-50">
        <h1 class="text-2xl font-black uppercase tracking-tight">Cashflow Orang</h1>
        <span class="neo-badge bg-lime-300">Tanpa Client / Project</span>
    </div>

    <div class="flex gap-0 min-h-[80vh]">
        <aside class="w-full md:w-72 shrink-0 border-4 border-black bg-gradient-to-b from-lime-50 to-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] md:mr-6 mb-6 md:mb-0 self-start">
            <div class="border-b-4 border-black bg-lime-400 px-4 py-3 flex items-center justify-between">
                <span class="font-black uppercase text-sm">Daftar Orang</span>
                <div class="flex items-center gap-2">
                    <button wire:click="toggleManagePeopleMode" class="neo-btn px-2 py-0.5 text-xs {{ $managePeopleMode ? 'bg-black text-lime-400' : 'bg-white text-black' }}">Pengaturan</button>
                    <button wire:click="$toggle('showNewPersonForm')" class="neo-btn bg-black text-lime-400 px-2 py-0.5 text-xs">+ New</button>
                </div>
            </div>

            @if($showNewPersonForm)
                <div class="border-b-4 border-black p-3 bg-lime-100 space-y-2">
                    <input wire:model="newPersonName" placeholder="Nama orang" class="neo-input w-full text-sm" />
                    <div class="flex gap-2">
                        <button wire:click="createPerson" class="neo-btn bg-black text-lime-400 text-xs px-3 py-1">Simpan</button>
                        <button wire:click="$set('showNewPersonForm', false)" class="neo-btn bg-white text-black text-xs px-3 py-1">Batal</button>
                    </div>
                </div>
            @endif

            <div class="overflow-y-auto max-h-[72vh] divide-y-2 divide-black">
                @forelse($people as $person)
                    <div class="px-3 py-2 {{ $selectedPersonId === $person->id ? 'bg-lime-400 border-l-4 border-black' : 'hover:bg-lime-50' }}">
                        <div class="flex items-start gap-2">
                            @if($managePeopleMode && $editingPersonId === $person->id)
                                <div class="flex-1 space-y-2">
                                    <input wire:model="editingPersonName" class="neo-input w-full text-sm" />
                                    <div class="flex gap-2">
                                        <button wire:click="savePersonName" class="neo-btn bg-black text-lime-400 text-[10px] px-2 py-0.5">Simpan</button>
                                        <button wire:click="cancelEditPerson" class="neo-btn bg-white text-black text-[10px] px-2 py-0.5">Batal</button>
                                    </div>
                                </div>
                            @else
                                <button wire:click="selectPerson({{ $person->id }})" class="flex-1 text-left">
                                    <p class="font-black text-sm truncate">{{ $person->name }}</p>
                                    <p class="text-xs font-bold text-gray-700 mt-1">Balance: Rp {{ number_format((float) $person->balance_total, 0, ',', '.') }}</p>
                                </button>
                                @if($managePeopleMode)
                                    <div class="flex flex-col gap-1">
                                        <button wire:click="startEditPerson({{ $person->id }})" class="neo-btn bg-white text-black text-[10px] px-2 py-0.5">Ubah</button>
                                        <button wire:click="deletePerson({{ $person->id }})" wire:confirm="Hapus orang ini beserta semua cashflow-nya?" class="neo-btn bg-red-400 text-white text-[10px] px-2 py-0.5">Hapus</button>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm font-bold text-gray-500">Belum ada orang. Tambah dulu dari tombol New.</div>
                @endforelse
            </div>
        </aside>

        <div class="flex-1 min-w-0 space-y-6">
            @if($selectedPerson)
                <div class="neo-card p-4 bg-lime-50 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-black text-lg uppercase">Cashflow: {{ $selectedPerson->name }}</h2>
                        <div class="flex items-center gap-2">
                            <div>
                                <label class="neo-label">Dari</label>
                                <input type="date" wire:model.live="dateFrom" class="neo-input text-sm" />
                            </div>
                            <div>
                                <label class="neo-label">Sampai</label>
                                <input type="date" wire:model.live="dateTo" class="neo-input text-sm" />
                            </div>
                            <div>
                                <label class="neo-label">Rows</label>
                                <select wire:model.live="perPage" class="neo-input text-sm min-w-[88px]">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach($entryRows as $index => $row)
                            <div wire:key="cashflow-row-{{ $index }}" class="border-4 border-black bg-white p-3 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                                <div>
                                    <label class="neo-label">Jenis</label>
                                    <select wire:model.live="entryRows.{{ $index }}.entry_type" class="neo-input w-full text-sm">
                                        <option value="masuk">Masuk</option>
                                        <option value="keluar">Keluar</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="neo-label">Harga</label>
                                    <input type="text" inputmode="numeric" wire:model.live="entryRows.{{ $index }}.price" class="neo-input w-full text-sm" />
                                </div>
                                <div>
                                    <label class="neo-label">Jumlah</label>
                                    <input type="number" min="1" step="1" wire:model.live="entryRows.{{ $index }}.quantity" class="neo-input w-full text-sm" />
                                </div>
                                <div>
                                    <label class="neo-label">Total</label>
                                    <input value="{{ $row['total'] }}" readonly class="neo-input w-full text-sm bg-gray-50" />
                                </div>
                                <div>
                                    <label class="neo-label">Tanggal</label>
                                    <input type="date" wire:model.live="entryRows.{{ $index }}.entry_date" class="neo-input w-full text-sm" />
                                </div>
                                <div>
                                    <button type="button" wire:click="removeRow({{ $index }})" class="neo-btn bg-red-400 text-white w-full text-sm">Hapus</button>
                                </div>
                                <div class="md:col-span-6">
                                    <label class="neo-label">Keterangan</label>
                                    <input type="text" wire:model.live="entryRows.{{ $index }}.description" class="neo-input w-full text-sm" placeholder="Catatan transaksi" />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex gap-2">
                        <button type="button" wire:click="addRow" class="neo-btn bg-white text-black">+ Tambah Baris</button>
                        <button type="button" wire:click="saveEntries" class="neo-btn bg-black text-lime-400">Simpan Cashflow</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <div class="xl:col-span-2 neo-card p-4 space-y-4 bg-white">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-black text-lg uppercase">Riwayat</h2>
                            <span class="neo-badge bg-white">{{ $entries->total() }} item</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($entries as $entry)
                                <div class="border-4 border-black p-3 {{ $entry->entry_type === 'masuk' ? 'bg-lime-50' : 'bg-red-50' }}">
                                    @if($editingEntryId === $entry->id)
                                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                                            <div>
                                                <label class="neo-label">Jenis</label>
                                                <select wire:model.live="editEntryType" class="neo-input w-full text-sm">
                                                    <option value="masuk">Masuk</option>
                                                    <option value="keluar">Keluar</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="neo-label">Harga</label>
                                                <input type="text" inputmode="numeric" wire:model.live="editPrice" class="neo-input w-full text-sm" />
                                            </div>
                                            <div>
                                                <label class="neo-label">Jumlah</label>
                                                <input type="number" min="1" step="1" wire:model.live="editQuantity" class="neo-input w-full text-sm" />
                                            </div>
                                            <div>
                                                <label class="neo-label">Total</label>
                                                <input value="{{ $editTotal }}" readonly class="neo-input w-full text-sm bg-gray-50" />
                                            </div>
                                            <div>
                                                <label class="neo-label">Tanggal</label>
                                                <input type="date" wire:model.live="editEntryDate" class="neo-input w-full text-sm" />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="neo-label">Keterangan</label>
                                                <input type="text" wire:model.live="editDescription" class="neo-input w-full text-sm" />
                                            </div>
                                            <div class="md:col-span-6 flex gap-2 justify-end">
                                                <button type="button" wire:click="cancelEditEntry" class="neo-btn bg-white text-black text-sm px-3 py-1">Batal</button>
                                                <button type="button" wire:click="saveEditEntry" class="neo-btn bg-black text-lime-400 text-sm px-3 py-1">Simpan</button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="neo-badge {{ $entry->entry_type === 'masuk' ? 'bg-lime-300' : 'bg-red-300' }}">{{ strtoupper($entry->entry_type) }}</span>
                                                    <span class="text-xs font-bold text-gray-500">{{ $entry->entry_date?->format('d M Y') ?? $entry->created_at->format('d M Y') }}</span>
                                                </div>
                                                @if($entry->description)
                                                    <p class="text-sm font-bold text-gray-700 mt-2">{{ $entry->description }}</p>
                                                @endif
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-bold">Harga: Rp {{ number_format((float) $entry->price, 0, ',', '.') }}</p>
                                                <p class="text-sm font-bold">Jumlah: {{ $entry->quantity }}</p>
                                                <p class="text-lg font-black">Total: Rp {{ number_format((float) $entry->total, 0, ',', '.') }}</p>
                                                <div class="mt-2 flex gap-2 justify-end">
                                                    <button type="button" wire:click="startEditEntry({{ $entry->id }})" class="neo-btn bg-white text-black text-xs px-2 py-1">Edit</button>
                                                    <button type="button" wire:click="deleteEntry({{ $entry->id }})" wire:confirm="Hapus catatan ini?" class="neo-btn bg-red-400 text-white text-xs px-2 py-1">Hapus</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="border-4 border-dashed border-black p-6 text-center text-sm font-bold text-gray-500">Belum ada cashflow untuk orang ini.</div>
                            @endforelse
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 border-t-4 border-black pt-4">
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
                            {{ $entries->links() }}
                        </div>
                    </div>

                    <aside class="space-y-6">
                        <div class="neo-card p-4 bg-white space-y-3">
                            <h2 class="font-black text-lg uppercase">Ringkasan</h2>
                            <div>
                                <label class="neo-label">Subtotal Grouping</label>
                                <select wire:model.live="summaryGroupBy" class="neo-input w-full text-sm">
                                    <option value="day">Per Hari</option>
                                    <option value="month">Per Bulan</option>
                                </select>
                            </div>
                            <div class="border-4 border-black bg-lime-50 p-3">
                                <p class="text-xs font-black uppercase text-gray-500">Total Masuk</p>
                                <p class="text-2xl font-black">Rp {{ number_format($income, 0, ',', '.') }}</p>
                            </div>
                            <div class="border-4 border-black bg-red-50 p-3">
                                <p class="text-xs font-black uppercase text-gray-500">Total Keluar</p>
                                <p class="text-2xl font-black">Rp {{ number_format($expense, 0, ',', '.') }}</p>
                            </div>
                            <div class="border-4 border-black {{ $balance >= 0 ? 'bg-lime-300' : 'bg-orange-300' }} p-3">
                                <p class="text-xs font-black uppercase text-gray-700">Saldo</p>
                                <p class="text-3xl font-black">Rp {{ number_format($balance, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        <div class="neo-card p-4 bg-white space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="font-black text-lg uppercase">Subtotal {{ $summaryGroupBy === 'month' ? 'Per Bulan' : 'Per Hari' }}</h2>
                                <span class="neo-badge bg-white">{{ $groupSummaries->count() }} periode</span>
                            </div>

                            <div class="space-y-2 max-h-[24rem] overflow-y-auto">
                                @forelse($groupSummaries as $summary)
                                    <div class="border-4 border-black p-3 {{ $summary['balance_total'] >= 0 ? 'bg-lime-50' : 'bg-red-50' }}">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-black">{{ $summary['period_key'] }}</p>
                                                <p class="text-xs font-bold text-gray-500">Masuk dan keluar pada periode ini</p>
                                            </div>
                                            <div class="text-right text-sm font-bold">
                                                <p>Masuk: Rp {{ number_format($summary['income_total'], 0, ',', '.') }}</p>
                                                <p>Keluar: Rp {{ number_format($summary['expense_total'], 0, ',', '.') }}</p>
                                                <p class="text-base font-black">Saldo: Rp {{ number_format($summary['balance_total'], 0, ',', '.') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="border-4 border-dashed border-black p-4 text-sm font-bold text-gray-500">Belum ada subtotal untuk filter saat ini.</div>
                                @endforelse
                            </div>
                        </div>
                    </aside>
                </div>
            @else
                <div class="border-4 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] p-12 text-center">
                    <p class="text-4xl mb-4">💸</p>
                    <p class="font-black text-xl">Pilih orang di sidebar kiri</p>
                    <p class="text-sm font-bold text-gray-500 mt-1">Cashflow akan tampil di panel kanan setelah dipilih.</p>
                </div>
            @endif
        </div>
    </div>
</div>
