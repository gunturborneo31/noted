<div class="space-y-6" x-data="aiRecorder($wire)">
    <div class="flex items-center gap-3">
        <h1 class="text-3xl font-black uppercase tracking-tight">AI Capture</h1>
        <span class="neo-badge bg-lime-300">Audio / Text</span>
    </div>

    @error('ai')
        <div class="border-4 border-black bg-red-200 p-3 font-bold text-sm">{{ $message }}</div>
    @enderror

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <div class="xl:col-span-3 neo-card p-4 bg-lime-50 space-y-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div>
                <label class="neo-label">Teks / Catatan Tambahan</label>
                <textarea wire:model="textInput" rows="10" class="neo-input w-full text-sm" placeholder="Tempel briefing, transkrip singkat, atau catatan tambahan untuk AI."></textarea>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="neo-label">Mode Ekstraksi</label>
                    <select wire:model.live="captureMode" class="neo-input w-full text-sm">
                        <option value="mixed">Campuran: Task + Account + Note</option>
                        <option value="tasks">Fokus Task</option>
                        <option value="accounts">Fokus Account</option>
                        <option value="detail_note">Fokus Catatan Detail</option>
                    </select>
                </div>
                <div>
                    <label class="neo-label">Label Draft / Riwayat</label>
                    <input wire:model.live="historyLabel" class="neo-input w-full text-sm" placeholder="Contoh: Meeting Client A, Rekaman 7 Juli" />
                </div>
                <div>
                    <label class="neo-label">Upload Rekaman</label>
                    <input type="file" wire:model="audioFile" accept=".mp3,.wav,.m4a,.mp4,.webm,.ogg,audio/*" class="neo-input w-full text-sm file:mr-3 file:border-0 file:bg-lime-300 file:px-3 file:py-1 file:font-bold" />
                    <p class="mt-2 text-xs font-bold text-gray-500">Format: mp3, wav, m4a, mp4, webm, ogg. Maks 20MB.</p>
                    @error('audioFile') <p class="text-red-600 text-xs mt-2 font-bold">{{ $message }}</p> @enderror
                </div>
                <div class="border-4 border-black bg-white p-3 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-black text-sm uppercase">Recorder Langsung</p>
                        <span class="text-xs font-bold" x-text="status"></span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" x-on:click="start()" x-bind:disabled="isRecording || isUploading" class="neo-btn bg-black text-lime-400 text-xs px-3 py-1">Mulai Rekam</button>
                        <button type="button" x-on:click="stop()" x-bind:disabled="!isRecording" class="neo-btn bg-white text-black text-xs px-3 py-1">Stop</button>
                        <button type="button" x-on:click="clearRecording()" x-bind:disabled="isRecording" class="neo-btn bg-white text-black text-xs px-3 py-1">Clear</button>
                    </div>
                    <audio x-show="audioUrl" x-bind:src="audioUrl" controls class="w-full"></audio>
                    <p class="text-xs font-bold text-gray-500">Rekaman akan langsung dimasukkan ke field upload dan bisa diproses dengan AI.</p>
                </div>
                <div class="border-4 border-black bg-white p-3 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-black text-sm uppercase">Transkrip Browser</p>
                        <span class="text-xs font-bold" x-text="speechStatus"></span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" x-on:click="startSpeech()" x-bind:disabled="isSpeechRecording" class="neo-btn bg-black text-lime-400 text-xs px-3 py-1">Mulai Transkrip</button>
                        <button type="button" x-on:click="stopSpeech()" x-bind:disabled="!isSpeechRecording" class="neo-btn bg-white text-black text-xs px-3 py-1">Stop</button>
                        <button type="button" x-on:click="clearSpeech()" class="neo-btn bg-white text-black text-xs px-3 py-1">Clear Teks</button>
                    </div>
                    <p class="text-xs font-bold text-gray-500">Fallback ini memakai fitur browser untuk mengisi area teks di kiri jika Anda tidak ingin upload audio.</p>
                </div>
                <div class="border-4 border-black bg-white p-3 text-sm font-bold space-y-2">
                    <p>AI akan mencoba mengenali:</p>
                    <p>1. Client</p>
                    <p>2. Project</p>
                    <p>3. Task dan detailnya</p>
                    <p>4. Account / kredensial jika ada</p>
                    <p>5. Catatan detail untuk Notes</p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="analyze" class="neo-btn bg-black text-lime-400 flex-1" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="analyze">Proses Dengan AI</span>
                        <span wire:loading wire:target="analyze">Memproses...</span>
                    </button>
                    <button type="button" wire:click="saveDraft" class="neo-btn bg-white text-black" @disabled(!$analysisReady && trim($textInput) === '')>
                        Save Draft
                    </button>
                </div>
            </div>
        </div>
        </div>

        <aside class="neo-card p-4 bg-white space-y-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-black text-lg uppercase">Riwayat AI</h2>
                @if($currentDraftId)
                    <span class="neo-badge bg-lime-300">Draft #{{ $currentDraftId }}</span>
                @endif
            </div>
            <div class="space-y-2 max-h-[32rem] overflow-y-auto">
                @forelse($historyItems as $history)
                    <div class="border-4 border-black p-3 {{ $currentDraftId === $history->id ? 'bg-lime-100' : $history->status_card_class }} space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="inline-flex border-2 border-black px-2 py-0.5 text-[11px] font-black uppercase {{ $history->status_badge_class }}">{{ $history->status_label }}</span>
                                <p class="font-bold text-sm">{{ $history->note_title ?: ($history->summary ? \Illuminate\Support\Str::limit($history->summary, 40) : 'AI Capture') }}</p>
                                <p class="text-[11px] font-bold text-gray-500">{{ $history->updated_at?->diffForHumans() }}</p>
                            </div>
                            <div class="flex gap-1">
                                <button type="button" wire:click="loadHistory({{ $history->id }})" class="neo-btn bg-white text-black text-xs px-2 py-1">Load</button>
                                <button type="button" wire:click="duplicateHistory({{ $history->id }})" class="neo-btn bg-white text-black text-xs px-2 py-1">Duplikat</button>
                                <button type="button" wire:click="deleteHistory({{ $history->id }})" wire:confirm="Hapus riwayat AI ini?" class="neo-btn bg-red-400 text-white text-xs px-2 py-1">Hapus</button>
                            </div>
                        </div>
                        @if($history->label)
                            <p class="text-xs font-bold text-lime-700">{{ $history->label }}</p>
                        @endif
                        <p class="text-xs font-bold text-gray-600">{{ strtoupper($history->capture_mode) }} / {{ strtoupper($history->classification) }}</p>
                    </div>
                @empty
                    <div class="border-4 border-dashed border-black p-4 text-sm font-bold text-gray-500">Belum ada riwayat AI.</div>
                @endforelse
            </div>
        </aside>
    </div>

    @if($analysisReady)
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div class="neo-card p-4 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-black text-lg uppercase">Hasil Analisis</h2>
                        <span class="neo-badge bg-white">{{ strtoupper($classification) }}</span>
                    </div>

                    <div>
                        <label class="neo-label">Ringkasan</label>
                        <textarea wire:model="summary" rows="4" class="neo-input w-full text-sm"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="neo-label">Client Hasil AI</label>
                            <input wire:model="clientName" class="neo-input w-full text-sm" />
                        </div>
                        <div>
                            <label class="neo-label">Project Hasil AI</label>
                            <input wire:model="projectName" class="neo-input w-full text-sm" />
                        </div>
                    </div>
                </div>

                <div class="neo-card p-4 space-y-5 bg-lime-50">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-black text-lg uppercase">Listing Tujuan Simpan</h2>
                        <span class="neo-badge bg-white">Review dulu sebelum save</span>
                    </div>

                    <div class="border-4 border-black bg-white p-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-black uppercase text-sm">Tasks</p>
                                <p class="text-xs font-bold text-gray-500">Bisa ditambahkan ke client/project pilihan Anda.</p>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm font-bold">
                                <input type="checkbox" wire:model.live="saveTasks" class="w-4 h-4" />
                                Simpan jadi Task
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="neo-label">Client Tujuan</label>
                                <div class="flex flex-wrap gap-2">
                                    <label class="inline-flex items-center gap-2 text-sm font-bold border-2 border-black bg-white px-3 py-1">
                                        <input type="radio" wire:model.live="taskClientMode" value="existing" />
                                        Pakai Existing
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm font-bold border-2 border-black bg-white px-3 py-1">
                                        <input type="radio" wire:model.live="taskClientMode" value="new" />
                                        Buat Baru
                                    </label>
                                </div>
                                @if($taskClientMode === 'existing')
                                    <select wire:model.live="selectedClientId" class="neo-input w-full text-sm">
                                        <option value="">Pilih client existing</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input wire:model.live="clientName" class="neo-input w-full text-sm" placeholder="Nama client baru" />
                                @endif
                                @if($matchedClient && $taskClientMode === 'existing')
                                    <p class="text-xs font-bold text-lime-700">AI menemukan client existing yang cocok: {{ $matchedClient->name }}</p>
                                @elseif(!$matchedClient && trim($clientName) !== '' && $taskClientMode === 'new')
                                    <p class="text-xs font-bold text-amber-700">Client belum ditemukan. Akan dibuat baru saat disimpan.</p>
                                @endif
                                @if(trim($clientName) !== '' && count($clientSuggestions) > 0 && ($clientSuggestions[0]['id'] ?? null))
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        @foreach($clientSuggestions as $suggestion)
                                            @if(($suggestion['id'] ?? null) && ($suggestion['score'] ?? 0) > 45)
                                                <button type="button" wire:click="applySuggestedClient({{ $suggestion['id'] }})" class="neo-btn bg-white text-black text-xs px-2 py-1">
                                                    {{ $suggestion['name'] }} ({{ (int) $suggestion['score'] }}%)
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <label class="neo-label">Project Tujuan</label>
                                <div class="flex flex-wrap gap-2">
                                    <label class="inline-flex items-center gap-2 text-sm font-bold border-2 border-black bg-white px-3 py-1">
                                        <input type="radio" wire:model.live="taskProjectMode" value="existing" />
                                        Pakai Existing
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm font-bold border-2 border-black bg-white px-3 py-1">
                                        <input type="radio" wire:model.live="taskProjectMode" value="new" />
                                        Buat Baru
                                    </label>
                                </div>
                                @if($taskProjectMode === 'existing')
                                    <select wire:model.live="selectedProjectId" class="neo-input w-full text-sm" @disabled(!$selectedClientId)>
                                        <option value="">Pilih project existing</option>
                                        @foreach($availableProjects as $project)
                                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input wire:model.live="projectName" class="neo-input w-full text-sm" placeholder="Nama project baru" />
                                @endif
                                @if($matchedProjectInClient && $taskProjectMode === 'existing')
                                    <p class="text-xs font-bold text-lime-700">AI menemukan project existing yang cocok pada client ini: {{ $matchedProjectInClient->project_name }}</p>
                                @elseif($sameNameProjectOtherClient)
                                    <div class="border-2 border-black bg-amber-100 p-2 text-xs font-bold">
                                        Nama project ini sudah ada di client lain: {{ $sameNameProjectOtherClient->client->name }}. Jika tidak terkait, tetap gunakan mode Buat Baru atau pilih project existing yang sesuai.
                                    </div>
                                @elseif($taskProjectMode === 'new' && trim($projectName) !== '')
                                    <p class="text-xs font-bold text-amber-700">Project akan dibuat baru di client tujuan saat disimpan.</p>
                                @endif
                                @if(trim($projectName) !== '' && count($projectSuggestions) > 0 && ($projectSuggestions[0]['id'] ?? null))
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        @foreach($projectSuggestions as $suggestion)
                                            @if(($suggestion['id'] ?? null) && ($suggestion['score'] ?? 0) > 45)
                                                <button type="button" wire:click="applySuggestedProject({{ $suggestion['id'] }})" class="neo-btn bg-white text-black text-xs px-2 py-1">
                                                    {{ $suggestion['name'] }} ({{ (int) $suggestion['score'] }}%)
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="border-4 border-black bg-white p-4 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-black uppercase text-sm">Accounts</p>
                                <p class="text-xs font-bold text-gray-500">Pilih folder existing, atau biarkan otomatis ke Client -> Project.</p>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm font-bold">
                                <input type="checkbox" wire:model.live="saveAccounts" class="w-4 h-4" />
                                Simpan jadi Account
                            </label>
                        </div>
                        <select wire:model.live="selectedAccountFolderId" class="neo-input w-full text-sm">
                            <option value="">Otomatis buat/ikuti Client -> Project</option>
                            @foreach($accountFolders as $folder)
                                <option value="{{ $folder->id }}">{{ $folder->full_path }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="border-4 border-black bg-white p-4 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-black uppercase text-sm">Catatan Detail</p>
                                <p class="text-xs font-bold text-gray-500">Akan disimpan sebagai note baru setelah Anda review isi catatannya.</p>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm font-bold">
                                <input type="checkbox" wire:model.live="saveDetailNote" class="w-4 h-4" />
                                Simpan ke Notes
                            </label>
                        </div>
                    </div>

                    <div class="border-4 border-black bg-black text-lime-400 p-4 space-y-3 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-black uppercase text-sm">Preview Simpan</p>
                            <span class="text-xs font-black uppercase">Sebelum Database</span>
                        </div>
                        <div class="space-y-2 text-sm font-bold">
                            <p>Tasks: {{ $saveTasks ? $selectedTaskCount : 0 }} item</p>
                            @if($saveTasks)
                                <p>Tujuan Task: {{ $previewClientName !== '' ? $previewClientName : '(client kosong)' }} / {{ $previewProjectName !== '' ? $previewProjectName : '(project kosong)' }}</p>
                            @endif
                            <p>Accounts: {{ $saveAccounts ? $selectedAccountCount : 0 }} item</p>
                            <p>Note Detail: {{ $saveDetailNote ? 'Ya' : 'Tidak' }}</p>
                            @if($saveDetailNote)
                                <p>Judul Note: {{ trim($noteTitle) !== '' ? $noteTitle : '(judul kosong)' }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="neo-card p-4 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-black text-lg uppercase">Task Terdeteksi</h2>
                        <div class="flex items-center gap-2">
                            <span class="neo-badge bg-white">{{ $selectedTaskCount }}/{{ count($generatedTasks) }} dipilih</span>
                            <button type="button" wire:click="selectAllTasks" class="neo-btn bg-white text-black text-xs px-2 py-1">Pilih Semua</button>
                            <button type="button" wire:click="clearTaskSelection" class="neo-btn bg-white text-black text-xs px-2 py-1">Clear</button>
                        </div>
                    </div>

                    @forelse($generatedTasks as $index => $task)
                        <div wire:key="task-row-{{ $index }}" class="border-4 border-black bg-lime-50 p-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div class="md:col-span-4 flex items-center justify-between gap-2 border-b-2 border-black pb-2">
                                <label class="inline-flex items-center gap-2 text-sm font-bold">
                                    <input type="checkbox" wire:model.live="generatedTasks.{{ $index }}.selected" class="w-4 h-4" />
                                    Masukkan task ini saat save
                                </label>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500">Task #{{ $index + 1 }}</span>
                                    <button type="button" wire:click="removeGeneratedTask({{ $index }})" class="neo-btn bg-red-400 text-white text-xs px-2 py-0.5">Hapus</button>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="neo-label">Nama Task</label>
                                <input wire:model="generatedTasks.{{ $index }}.name" class="neo-input w-full text-sm" />
                            </div>
                            <div>
                                <label class="neo-label">Status</label>
                                <select wire:model="generatedTasks.{{ $index }}.status" class="neo-input w-full text-sm">
                                    <option value="todo">To Do</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="done">Done</option>
                                </select>
                            </div>
                            <div>
                                <label class="neo-label">Due Date</label>
                                <input type="date" wire:model="generatedTasks.{{ $index }}.due_date" class="neo-input w-full text-sm" />
                            </div>
                            <div class="md:col-span-4">
                                <label class="neo-label">Detail</label>
                                <textarea wire:model="generatedTasks.{{ $index }}.detail" rows="3" class="neo-input w-full text-sm"></textarea>
                            </div>
                        </div>
                    @empty
                        <div class="border-4 border-dashed border-black p-4 text-sm font-bold text-gray-500">AI tidak menemukan task yang jelas dari input ini.</div>
                    @endforelse
                </div>

                <div class="neo-card p-4 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-black text-lg uppercase">Akun / Credential</h2>
                        <div class="flex items-center gap-2">
                            <span class="neo-badge bg-white">{{ $selectedAccountCount }}/{{ count($generatedAccounts) }} dipilih</span>
                            <button type="button" wire:click="selectAllAccounts" class="neo-btn bg-white text-black text-xs px-2 py-1">Pilih Semua</button>
                            <button type="button" wire:click="clearAccountSelection" class="neo-btn bg-white text-black text-xs px-2 py-1">Clear</button>
                        </div>
                    </div>

                    @forelse($generatedAccounts as $index => $account)
                        <div wire:key="account-row-{{ $index }}" class="border-4 border-black bg-lime-50 p-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div class="md:col-span-4 flex items-center justify-between gap-2 border-b-2 border-black pb-2">
                                <label class="inline-flex items-center gap-2 text-sm font-bold">
                                    <input type="checkbox" wire:model.live="generatedAccounts.{{ $index }}.selected" class="w-4 h-4" />
                                    Masukkan account ini saat save
                                </label>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500">Account #{{ $index + 1 }}</span>
                                    <button type="button" wire:click="removeGeneratedAccount({{ $index }})" class="neo-btn bg-red-400 text-white text-xs px-2 py-0.5">Hapus</button>
                                </div>
                            </div>
                            <div>
                                <label class="neo-label">Platform</label>
                                <input wire:model="generatedAccounts.{{ $index }}.platform" class="neo-input w-full text-sm" />
                            </div>
                            <div>
                                <label class="neo-label">Login Type</label>
                                <select wire:model="generatedAccounts.{{ $index }}.login_type" class="neo-input w-full text-sm">
                                    <option value="credentials">Username/Password</option>
                                    <option value="google">Google</option>
                                    <option value="email">Email</option>
                                </select>
                            </div>
                            <div>
                                <label class="neo-label">Username</label>
                                <input wire:model="generatedAccounts.{{ $index }}.username" class="neo-input w-full text-sm" />
                            </div>
                            <div>
                                <label class="neo-label">Password</label>
                                <input wire:model="generatedAccounts.{{ $index }}.password" class="neo-input w-full text-sm" />
                            </div>
                            <div class="md:col-span-4">
                                <label class="neo-label">Detail</label>
                                <textarea wire:model="generatedAccounts.{{ $index }}.detail" rows="2" class="neo-input w-full text-sm"></textarea>
                            </div>
                        </div>
                    @empty
                        <div class="border-4 border-dashed border-black p-4 text-sm font-bold text-gray-500">AI tidak menemukan data akun yang jelas dari input ini.</div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div class="neo-card p-4 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-black text-lg uppercase">Catatan Detail</h2>
                        <span class="neo-badge bg-white">Editable sebelum save</span>
                    </div>
                    <div class="space-y-2">
                        <label class="neo-label">Mode Simpan Note</label>
                        <div class="flex flex-wrap gap-2">
                            <label class="inline-flex items-center gap-2 text-sm font-bold border-2 border-black bg-white px-3 py-1">
                                <input type="radio" wire:model.live="noteSaveMode" value="new" />
                                Note Baru
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-bold border-2 border-black bg-white px-3 py-1">
                                <input type="radio" wire:model.live="noteSaveMode" value="existing" />
                                Gabung ke Note Existing
                            </label>
                        </div>
                        @if($noteSaveMode === 'existing')
                            <select wire:model.live="selectedNoteId" class="neo-input w-full text-sm">
                                <option value="">Pilih note existing</option>
                                @foreach($notes as $note)
                                    <option value="{{ $note->id }}">{{ $note->title }} ({{ $note->updated_at?->diffForHumans() }})</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div>
                        <label class="neo-label">Judul Note</label>
                        <input wire:model="noteTitle" class="neo-input w-full text-sm" />
                    </div>
                    <div>
                        <label class="neo-label">Isi Note</label>
                        <textarea wire:model="noteBody" rows="14" class="neo-input w-full text-sm"></textarea>
                    </div>
                </div>

                <div class="neo-card p-4 space-y-4 bg-white">
                    <h2 class="font-black text-lg uppercase">Transkrip / Sumber</h2>
                    <textarea rows="16" readonly class="neo-input w-full text-sm bg-gray-50">{{ $transcript }}</textarea>
                </div>

                @error('saveTargets')
                    <div class="border-4 border-black bg-red-200 p-3 font-bold text-sm">{{ $message }}</div>
                @enderror

                <div class="flex gap-2">
                    <button wire:click="saveDraft" class="neo-btn bg-white text-black flex-1" wire:loading.attr="disabled">
                        Save Draft
                    </button>
                    <button wire:click="exportText" class="neo-btn bg-white text-black flex-1" wire:loading.attr="disabled">
                        Export TXT
                    </button>
                    <button wire:click="exportMarkdown" class="neo-btn bg-white text-black flex-1" wire:loading.attr="disabled">
                        Export MD
                    </button>
                    <button wire:click="saveGenerated" class="neo-btn bg-black text-lime-400 flex-1" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveGenerated">Simpan Hasil AI</span>
                        <span wire:loading wire:target="saveGenerated">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        window.aiRecorder = window.aiRecorder || function aiRecorder(wire) {
            return {
                mediaRecorder: null,
                speechRecognition: null,
                chunks: [],
                audioUrl: '',
                isRecording: false,
                isUploading: false,
                isSpeechRecording: false,
                status: 'Belum ada rekaman',
                speechStatus: 'Belum aktif',
                async start() {
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.status = 'Browser tidak mendukung recorder';
                        return;
                    }

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        this.chunks = [];
                        this.mediaRecorder = new MediaRecorder(stream);
                        this.mediaRecorder.ondataavailable = (event) => {
                            if (event.data.size > 0) {
                                this.chunks.push(event.data);
                            }
                        };
                        this.mediaRecorder.onstop = () => this.finishRecording(stream);
                        this.mediaRecorder.start();
                        this.isRecording = true;
                        this.status = 'Sedang merekam...';
                    } catch (error) {
                        this.status = 'Izin mikrofon ditolak atau gagal merekam';
                    }
                },
                stop() {
                    if (this.mediaRecorder && this.isRecording) {
                        this.mediaRecorder.stop();
                        this.isRecording = false;
                        this.status = 'Memproses rekaman...';
                    }
                },
                clearRecording() {
                    this.audioUrl = '';
                    this.chunks = [];
                    this.status = 'Belum ada rekaman';
                    wire.set('audioFile', null);
                },
                startSpeech() {
                    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!Recognition) {
                        this.speechStatus = 'SpeechRecognition tidak didukung browser';
                        return;
                    }

                    this.speechRecognition = new Recognition();
                    this.speechRecognition.lang = 'id-ID';
                    this.speechRecognition.interimResults = true;
                    this.speechRecognition.continuous = true;

                    this.speechRecognition.onstart = () => {
                        this.isSpeechRecording = true;
                        this.speechStatus = 'Sedang mendengar...';
                    };

                    this.speechRecognition.onresult = (event) => {
                        let finalTranscript = '';
                        for (let index = 0; index < event.results.length; index++) {
                            finalTranscript += event.results[index][0].transcript + ' ';
                        }

                        wire.set('textInput', finalTranscript.trim());
                    };

                    this.speechRecognition.onerror = () => {
                        this.isSpeechRecording = false;
                        this.speechStatus = 'Gagal melakukan transkrip';
                    };

                    this.speechRecognition.onend = () => {
                        this.isSpeechRecording = false;
                        if (this.speechStatus === 'Sedang mendengar...') {
                            this.speechStatus = 'Transkrip selesai';
                        }
                    };

                    this.speechRecognition.start();
                },
                stopSpeech() {
                    if (this.speechRecognition && this.isSpeechRecording) {
                        this.speechRecognition.stop();
                    }
                },
                clearSpeech() {
                    wire.set('textInput', '');
                    this.speechStatus = 'Teks dibersihkan';
                },
                finishRecording(stream) {
                    stream.getTracks().forEach((track) => track.stop());

                    const blob = new Blob(this.chunks, { type: 'audio/webm' });
                    const file = new File([blob], `ai-capture-${Date.now()}.webm`, { type: 'audio/webm' });
                    this.audioUrl = URL.createObjectURL(blob);
                    this.isUploading = true;
                    this.status = 'Mengunggah rekaman...';

                    wire.upload('audioFile', file, () => {
                        this.isUploading = false;
                        this.status = 'Rekaman siap diproses';
                    }, () => {
                        this.isUploading = false;
                        this.status = 'Gagal mengunggah rekaman';
                    });
                },
            };
        };
    </script>
</div>
