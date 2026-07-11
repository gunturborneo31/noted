<?php

namespace App\Livewire;

use App\Models\AiCaptureHistory;
use App\Models\AccountEntry;
use App\Models\AccountFolder;
use App\Models\Client;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Services\AiCaptureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class AiCapture extends Component
{
    use WithFileUploads;

    public string $captureMode = 'mixed';
    public string $textInput = '';
    public $audioFile = null;

    public string $summary = '';
    public string $transcript = '';
    public string $classification = 'mixed';
    public string $clientName = '';
    public string $projectName = '';
    public string $noteTitle = '';
    public string $noteBody = '';
    public string $historyLabel = '';
    public string $noteSaveMode = 'new';
    public ?int $selectedNoteId = null;
    public ?int $currentDraftId = null;

    public array $generatedTasks = [];
    public array $generatedAccounts = [];

    public bool $saveTasks = true;
    public bool $saveAccounts = false;
    public bool $saveDetailNote = true;
    public ?int $selectedAccountFolderId = null;
    public string $taskClientMode = 'new';
    public ?int $selectedClientId = null;
    public string $taskProjectMode = 'new';
    public ?int $selectedProjectId = null;

    public bool $analysisReady = false;

    private const MATCH_CONFIDENCE_THRESHOLD = 82.0;

    public function analyze(AiCaptureService $aiCaptureService): void
    {
        $this->resetErrorBag('ai');

        try {
            $this->validate([
                'captureMode' => 'required|in:mixed,tasks,accounts,detail_note',
                'textInput' => 'nullable|string',
                'audioFile' => 'nullable|file|mimes:mp3,wav,m4a,mp4,webm,ogg|max:20480',
            ]);

            $result = $aiCaptureService->analyze($this->textInput, $this->audioFile, $this->captureMode);

            $this->summary = trim((string) ($result['summary'] ?? ''));
            $this->transcript = trim((string) ($result['transcript'] ?? ''));
            $this->classification = trim((string) ($result['classification'] ?? 'mixed')) ?: 'mixed';
            $this->clientName = trim((string) ($result['client_name'] ?? ''));
            $this->projectName = trim((string) ($result['project_name'] ?? ''));
            $this->noteTitle = trim((string) ($result['detail_note_title'] ?? '')) ?: 'AI Capture '.now()->format('d M Y H:i');
            $this->noteBody = trim((string) ($result['detail_note_body'] ?? ''));
            $this->generatedTasks = $this->normalizeTasks($result['tasks'] ?? []);
            $this->generatedAccounts = $this->normalizeAccounts($result['accounts'] ?? []);

            $this->saveTasks = count($this->generatedTasks) > 0;
            $this->saveAccounts = count($this->generatedAccounts) > 0;
            $this->saveDetailNote = true;
            $this->analysisReady = true;
            $this->syncDestinationSelections();

            if ($this->noteBody === '') {
                $this->noteBody = $this->buildDefaultNoteBody();
            }
        } catch (\Throwable $throwable) {
            $this->analysisReady = false;
            $this->addError('ai', $throwable->getMessage());
        }
    }

    public function saveGenerated(): void
    {
        if (!$this->analysisReady) {
            return;
        }

        $this->resetErrorBag('ai');

        try {
            $this->validate([
                'clientName' => 'nullable|string|max:255',
                'projectName' => 'nullable|string|max:255',
                'noteTitle' => 'nullable|string|max:255',
                'noteBody' => 'nullable|string',
                'selectedAccountFolderId' => 'nullable|exists:account_folders,id',
                'noteSaveMode' => 'required|in:new,existing',
                'selectedNoteId' => 'nullable|exists:notes,id',
                'taskClientMode' => 'required|in:existing,new',
                'selectedClientId' => 'nullable|exists:clients,id',
                'taskProjectMode' => 'required|in:existing,new',
                'selectedProjectId' => 'nullable|exists:projects,id',
                'generatedTasks.*.selected' => 'nullable|boolean',
                'generatedTasks.*.name' => 'nullable|string|max:255',
                'generatedTasks.*.detail' => 'nullable|string',
                'generatedTasks.*.status' => 'nullable|in:todo,in_progress,done',
                'generatedTasks.*.due_date' => 'nullable|date',
                'generatedAccounts.*.selected' => 'nullable|boolean',
                'generatedAccounts.*.platform' => 'nullable|string|max:255',
                'generatedAccounts.*.login_type' => 'nullable|in:credentials,google,email',
                'generatedAccounts.*.username' => 'nullable|string|max:255',
                'generatedAccounts.*.password' => 'nullable|string|max:255',
                'generatedAccounts.*.detail' => 'nullable|string',
            ]);

            if (!$this->saveTasks && !$this->saveAccounts && !$this->saveDetailNote) {
                $this->addError('saveTargets', 'Pilih minimal satu target simpan.');
                return;
            }

            $selectedTasks = array_values(array_filter($this->generatedTasks, fn($task) => !empty($task['selected']) && trim((string) ($task['name'] ?? '')) !== ''));
            $selectedAccounts = array_values(array_filter($this->generatedAccounts, fn($account) => !empty($account['selected']) && trim((string) ($account['platform'] ?? '')) !== ''));

            if ($this->saveTasks && count($selectedTasks) === 0) {
                $this->addError('saveTargets', 'Pilih minimal satu task jika opsi simpan task aktif.');
                return;
            }

            if ($this->saveAccounts && count($selectedAccounts) === 0) {
                $this->addError('saveTargets', 'Pilih minimal satu account jika opsi simpan account aktif.');
                return;
            }

            DB::transaction(function () use ($selectedTasks, $selectedAccounts): void {
                $project = null;

                if ($this->saveTasks) {
                    $project = $this->resolveProject();
                    foreach ($selectedTasks as $task) {
                        $name = trim((string) ($task['name'] ?? ''));
                        if ($name === '') {
                            continue;
                        }

                        Task::create([
                            'project_id' => $project->id,
                            'task_name' => $name,
                            'content' => trim((string) ($task['detail'] ?? '')),
                            'status' => $this->normalizeStatus((string) ($task['status'] ?? 'todo')),
                            'due_date' => trim((string) ($task['due_date'] ?? '')) ?: null,
                        ]);
                    }
                }

                if ($this->saveAccounts) {
                    $folderId = $this->resolveAccountFolderId();
                    foreach ($selectedAccounts as $account) {
                        $platform = trim((string) ($account['platform'] ?? ''));
                        if ($platform === '') {
                            continue;
                        }

                        $loginType = $this->normalizeLoginType((string) ($account['login_type'] ?? 'credentials'));
                        $username = trim((string) ($account['username'] ?? ''));
                        $password = trim((string) ($account['password'] ?? ''));
                        $detail = trim((string) ($account['detail'] ?? ''));

                        $accountValue = $loginType === 'credentials'
                            ? ($username !== '' ? $username : $detail)
                            : 'Masuk dengan '.ucfirst($loginType);

                        AccountEntry::create([
                            'folder_id' => $folderId,
                            'platform' => $platform,
                            'account_value' => $accountValue,
                            'username' => $loginType === 'credentials' ? ($username !== '' ? $username : null) : null,
                            'password' => $loginType === 'credentials' ? ($password !== '' ? $password : null) : null,
                            'login_type' => $loginType,
                        ]);
                    }
                }

                if ($this->saveDetailNote) {
                    $this->saveNoteResult();
                }
            });

            if ($this->currentDraftId) {
                AiCaptureHistory::where('id', $this->currentDraftId)
                    ->where('user_id', auth()->id() ?? 1)
                    ->update([
                        'status' => 'saved',
                        'processed_at' => now(),
                        'last_saved_at' => now(),
                    ]);
            }

            session()->flash('success', 'Hasil AI berhasil disimpan.');
            $this->resetCapture();
        } catch (\Throwable $throwable) {
            $this->addError('ai', $throwable->getMessage());
        }
    }

    public function render()
    {
        $clients = Client::query()
            ->with(['projects' => fn($query) => $query->orderBy('sort_order')->orderBy('project_name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $accountFolders = AccountFolder::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $notes = Note::query()
            ->where('user_id', auth()->id() ?? 1)
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        $historyItems = AiCaptureHistory::query()
            ->where('user_id', auth()->id() ?? 1)
            ->latest('updated_at')
            ->limit(12)
            ->get();

        $availableProjects = collect();
        if ($this->selectedClientId) {
            $availableProjects = Project::query()
                ->where('client_id', $this->selectedClientId)
                ->orderBy('sort_order')
                ->orderBy('project_name')
                ->get();
        }

        $clientSuggestions = $this->findClientSuggestions($clients);
        $matchedClient = $clientSuggestions[0]['score'] >= self::MATCH_CONFIDENCE_THRESHOLD
            ? ($clients->firstWhere('id', $clientSuggestions[0]['id']) ?? null)
            : null;

        $projectSuggestions = $this->findProjectSuggestions($availableProjects);
        $matchedProjectInClient = $projectSuggestions[0]['score'] >= self::MATCH_CONFIDENCE_THRESHOLD
            ? ($availableProjects->firstWhere('id', $projectSuggestions[0]['id']) ?? null)
            : null;

        $sameNameProjectOtherClient = ($this->selectedClientId && trim($this->projectName) !== '')
            ? Project::query()
                ->where('client_id', '!=', $this->selectedClientId)
                ->whereRaw('lower(project_name) = ?', [Str::lower(trim($this->projectName))])
                ->with('client:id,name')
                ->first()
            : null;

        $selectedTaskCount = collect($this->generatedTasks)->where('selected', true)->count();
        $selectedAccountCount = collect($this->generatedAccounts)->where('selected', true)->count();

        $previewClientName = $this->taskClientMode === 'existing'
            ? ($clients->firstWhere('id', $this->selectedClientId)?->name ?? '')
            : trim($this->clientName);

        $previewProjectName = $this->taskProjectMode === 'existing'
            ? ($availableProjects->firstWhere('id', $this->selectedProjectId)?->project_name ?? '')
            : trim($this->projectName);

        return view('livewire.ai-capture', compact('accountFolders', 'clients', 'availableProjects', 'matchedClient', 'matchedProjectInClient', 'sameNameProjectOtherClient', 'clientSuggestions', 'projectSuggestions', 'selectedTaskCount', 'selectedAccountCount', 'previewClientName', 'previewProjectName', 'notes', 'historyItems'));
    }

    public function saveDraft(): void
    {
        $history = AiCaptureHistory::updateOrCreate(
            [
                'id' => $this->currentDraftId,
                'user_id' => auth()->id() ?? 1,
            ],
            $this->historyPayload('draft')
        );

        $this->currentDraftId = $history->id;
        session()->flash('success', 'Draft AI berhasil disimpan.');
    }

    public function duplicateHistory(int $historyId): void
    {
        $history = AiCaptureHistory::query()
            ->where('user_id', auth()->id() ?? 1)
            ->findOrFail($historyId);

        $copy = $history->replicate();
        $copy->status = 'draft';
        $copy->label = trim(($history->label ?: 'Draft AI').' Copy');
        $copy->processed_at = null;
        $copy->last_saved_at = now();
        $copy->save();

        $this->loadHistory($copy->id);
        session()->flash('success', 'Draft AI berhasil diduplikasi.');
    }

    public function loadHistory(int $historyId): void
    {
        $history = AiCaptureHistory::query()
            ->where('user_id', auth()->id() ?? 1)
            ->findOrFail($historyId);

        $this->currentDraftId = $history->id;
        $this->captureMode = $history->capture_mode;
        $this->summary = $history->summary ?? '';
        $this->transcript = $history->transcript ?? '';
        $this->classification = $history->classification;
        $this->historyLabel = $history->label ?? '';
        $this->clientName = $history->client_name ?? '';
        $this->projectName = $history->project_name ?? '';
        $this->noteTitle = $history->note_title ?? '';
        $this->noteBody = $history->note_body ?? '';
        $this->generatedTasks = $history->generated_tasks ?? [];
        $this->generatedAccounts = $history->generated_accounts ?? [];
        $this->saveTasks = (bool) $history->save_tasks;
        $this->saveAccounts = (bool) $history->save_accounts;
        $this->saveDetailNote = (bool) $history->save_detail_note;
        $this->selectedAccountFolderId = $history->selected_account_folder_id;
        $this->taskClientMode = $history->task_client_mode;
        $this->selectedClientId = $history->selected_client_id;
        $this->taskProjectMode = $history->task_project_mode;
        $this->selectedProjectId = $history->selected_project_id;
        $this->noteSaveMode = $history->note_save_mode;
        $this->selectedNoteId = $history->selected_note_id;
        $this->analysisReady = true;
    }

    public function deleteHistory(int $historyId): void
    {
        AiCaptureHistory::query()
            ->where('user_id', auth()->id() ?? 1)
            ->where('id', $historyId)
            ->delete();

        if ($this->currentDraftId === $historyId) {
            $this->currentDraftId = null;
        }

        session()->flash('success', 'Riwayat AI dihapus.');
    }

    public function exportMarkdown()
    {
        return response()->streamDownload(function (): void {
            echo $this->buildExportContent('markdown');
        }, 'ai-capture-'.now()->format('Ymd-His').'.md', [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    public function exportText()
    {
        return response()->streamDownload(function (): void {
            echo $this->buildExportContent('text');
        }, 'ai-capture-'.now()->format('Ymd-His').'.txt', [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function updatedSelectedClientId($value): void
    {
        $this->selectedClientId = $value ? (int) $value : null;

        if (!$this->selectedClientId) {
            $this->taskProjectMode = 'new';
            $this->selectedProjectId = null;
            return;
        }

        if ($this->selectedProjectId) {
            $belongs = Project::query()
                ->where('id', $this->selectedProjectId)
                ->where('client_id', $this->selectedClientId)
                ->exists();

            if (!$belongs) {
                $this->selectedProjectId = null;
                $this->taskProjectMode = 'new';
            }
        }

        if (trim($this->projectName) !== '') {
            $availableProjects = Project::query()
                ->where('client_id', $this->selectedClientId)
                ->orderBy('sort_order')
                ->orderBy('project_name')
                ->get();

            $projectSuggestions = $this->findProjectSuggestions($availableProjects);
            if ($projectSuggestions[0]['score'] >= self::MATCH_CONFIDENCE_THRESHOLD) {
                $this->taskProjectMode = 'existing';
                $this->selectedProjectId = (int) $projectSuggestions[0]['id'];
            }
        }
    }

    public function updatedSelectedProjectId($value): void
    {
        $this->selectedProjectId = $value ? (int) $value : null;
    }

    public function updatedTaskClientMode(): void
    {
        if ($this->taskClientMode === 'new') {
            $this->selectedClientId = null;
            $this->taskProjectMode = 'new';
            $this->selectedProjectId = null;
        } elseif (trim($this->clientName) !== '') {
            $client = Client::query()->whereRaw('lower(name) = ?', [Str::lower(trim($this->clientName))])->first();
            if ($client) {
                $this->selectedClientId = $client->id;
            }
        }
    }

    public function updatedTaskProjectMode(): void
    {
        if ($this->taskProjectMode === 'new') {
            $this->selectedProjectId = null;
        }
    }

    public function updatedNoteSaveMode(): void
    {
        if ($this->noteSaveMode === 'new') {
            $this->selectedNoteId = null;
        }
    }

    public function applySuggestedClient(int $clientId): void
    {
        $client = Client::findOrFail($clientId);
        $this->taskClientMode = 'existing';
        $this->selectedClientId = $client->id;
        $this->clientName = $client->name;

        $this->updatedSelectedClientId($client->id);
    }

    public function applySuggestedProject(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $this->taskProjectMode = 'existing';
        $this->selectedProjectId = $project->id;
        $this->projectName = $project->project_name;

        if ($this->selectedClientId !== $project->client_id) {
            $this->taskClientMode = 'existing';
            $this->selectedClientId = $project->client_id;
            $this->clientName = $project->client->name;
        }
    }

    public function selectAllTasks(): void
    {
        foreach ($this->generatedTasks as $index => $task) {
            $this->generatedTasks[$index]['selected'] = true;
        }
    }

    public function clearTaskSelection(): void
    {
        foreach ($this->generatedTasks as $index => $task) {
            $this->generatedTasks[$index]['selected'] = false;
        }
    }

    public function selectAllAccounts(): void
    {
        foreach ($this->generatedAccounts as $index => $account) {
            $this->generatedAccounts[$index]['selected'] = true;
        }
    }

    public function clearAccountSelection(): void
    {
        foreach ($this->generatedAccounts as $index => $account) {
            $this->generatedAccounts[$index]['selected'] = false;
        }
    }

    public function removeGeneratedTask(int $index): void
    {
        if (!isset($this->generatedTasks[$index])) {
            return;
        }

        unset($this->generatedTasks[$index]);
        $this->generatedTasks = array_values($this->generatedTasks);

        if ($this->generatedTasks === []) {
            $this->saveTasks = false;
        }
    }

    public function removeGeneratedAccount(int $index): void
    {
        if (!isset($this->generatedAccounts[$index])) {
            return;
        }

        unset($this->generatedAccounts[$index]);
        $this->generatedAccounts = array_values($this->generatedAccounts);

        if ($this->generatedAccounts === []) {
            $this->saveAccounts = false;
        }
    }

    private function resolveProject(): Project
    {
        $clientName = trim($this->clientName);
        $projectName = trim($this->projectName);

        if ($clientName === '' || $projectName === '') {
            throw new \RuntimeException('Client dan project wajib ada jika ingin menyimpan task.');
        }

        if ($this->taskClientMode === 'existing') {
            if (!$this->selectedClientId) {
                throw new \RuntimeException('Pilih client existing atau ubah ke mode Buat Baru.');
            }

            $client = Client::findOrFail($this->selectedClientId);
        } else {
            $client = Client::query()
                ->whereRaw('lower(name) = ?', [Str::lower($clientName)])
                ->first();

            if (!$client) {
                $client = Client::create([
                    'name' => $clientName,
                    'sort_order' => ((int) Client::max('sort_order')) + 1,
                ]);
            }
        }

        if ($this->taskProjectMode === 'existing') {
            if (!$this->selectedProjectId) {
                throw new \RuntimeException('Pilih project existing atau ubah ke mode Buat Baru.');
            }

            $project = Project::query()
                ->where('id', $this->selectedProjectId)
                ->where('client_id', $client->id)
                ->first();

            if (!$project) {
                throw new \RuntimeException('Project existing yang dipilih tidak terkait dengan client tujuan. Pilih project lain atau buat baru.');
            }
        } else {
            $project = Project::query()
                ->where('client_id', $client->id)
                ->whereRaw('lower(project_name) = ?', [Str::lower($projectName)])
                ->first();

            if (!$project) {
                $project = Project::create([
                    'client_id' => $client->id,
                    'project_name' => $projectName,
                    'sort_order' => ((int) Project::where('client_id', $client->id)->max('sort_order')) + 1,
                ]);
            }
        }

        return $project;
    }

    private function resolveAccountFolderId(): int
    {
        if ($this->selectedAccountFolderId) {
            return $this->selectedAccountFolderId;
        }

        $rootName = trim($this->clientName) !== '' ? trim($this->clientName) : 'AI Inbox';
        $rootFolder = AccountFolder::query()
            ->whereNull('parent_id')
            ->whereRaw('lower(name) = ?', [Str::lower($rootName)])
            ->first();

        if (!$rootFolder) {
            $rootFolder = AccountFolder::create([
                'name' => $rootName,
                'parent_id' => null,
                'sort_order' => ((int) AccountFolder::whereNull('parent_id')->max('sort_order')) + 1,
            ]);
        }

        $projectName = trim($this->projectName);
        if ($projectName === '') {
            return $rootFolder->id;
        }

        $childFolder = AccountFolder::query()
            ->where('parent_id', $rootFolder->id)
            ->whereRaw('lower(name) = ?', [Str::lower($projectName)])
            ->first();

        if (!$childFolder) {
            $childFolder = AccountFolder::create([
                'name' => $projectName,
                'parent_id' => $rootFolder->id,
                'sort_order' => ((int) AccountFolder::where('parent_id', $rootFolder->id)->max('sort_order')) + 1,
            ]);
        }

        return $childFolder->id;
    }

    private function normalizeTasks(array $tasks): array
    {
        $normalized = [];

        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }

            $normalized[] = [
                'selected' => trim((string) ($task['name'] ?? '')) !== '',
                'name' => trim((string) ($task['name'] ?? '')),
                'detail' => trim((string) ($task['detail'] ?? '')),
                'status' => $this->normalizeStatus((string) ($task['status'] ?? 'todo')),
                'due_date' => trim((string) ($task['due_date'] ?? '')),
            ];
        }

        return $normalized;
    }

    private function normalizeAccounts(array $accounts): array
    {
        $normalized = [];

        foreach ($accounts as $account) {
            if (!is_array($account)) {
                continue;
            }

            $normalized[] = [
                'selected' => trim((string) ($account['platform'] ?? '')) !== '',
                'platform' => trim((string) ($account['platform'] ?? '')),
                'login_type' => $this->normalizeLoginType((string) ($account['login_type'] ?? 'credentials')),
                'username' => trim((string) ($account['username'] ?? '')),
                'password' => trim((string) ($account['password'] ?? '')),
                'detail' => trim((string) ($account['detail'] ?? '')),
            ];
        }

        return $normalized;
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'todo', 'in_progress', 'done' => $status,
            default => 'todo',
        };
    }

    private function normalizeLoginType(string $loginType): string
    {
        return match ($loginType) {
            'google', 'email', 'credentials' => $loginType,
            default => 'credentials',
        };
    }

    private function buildDefaultNoteBody(): string
    {
        $sections = [];

        if (trim($this->summary) !== '') {
            $sections[] = "## Ringkasan\n".trim($this->summary);
        }

        if (trim($this->noteBody) !== '') {
            $sections[] = trim($this->noteBody);
        }

        if (trim($this->transcript) !== '') {
            $sections[] = "## Transkrip / Input\n".trim($this->transcript);
        }

        return implode("\n\n", array_filter($sections));
    }

    private function resetCapture(): void
    {
        $this->reset([
            'captureMode',
            'textInput',
            'audioFile',
            'summary',
            'transcript',
            'classification',
            'clientName',
            'projectName',
            'noteTitle',
            'noteBody',
            'historyLabel',
            'noteSaveMode',
            'selectedNoteId',
            'currentDraftId',
            'generatedTasks',
            'generatedAccounts',
            'selectedAccountFolderId',
            'taskClientMode',
            'selectedClientId',
            'taskProjectMode',
            'selectedProjectId',
            'analysisReady',
        ]);

        $this->captureMode = 'mixed';
        $this->classification = 'mixed';
        $this->noteSaveMode = 'new';
        $this->saveTasks = true;
        $this->saveAccounts = false;
        $this->saveDetailNote = true;
    }

    private function syncDestinationSelections(): void
    {
        $clients = Client::query()->orderBy('sort_order')->orderBy('name')->get();
        $clientSuggestions = $this->findClientSuggestions($clients);
        $matchedClient = $clientSuggestions[0]['score'] >= self::MATCH_CONFIDENCE_THRESHOLD
            ? ($clients->firstWhere('id', $clientSuggestions[0]['id']) ?? null)
            : null;

        if ($matchedClient) {
            $this->taskClientMode = 'existing';
            $this->selectedClientId = $matchedClient->id;
        } else {
            $this->taskClientMode = 'new';
            $this->selectedClientId = null;
        }

        $availableProjects = $this->selectedClientId
            ? Project::query()->where('client_id', $this->selectedClientId)->orderBy('sort_order')->orderBy('project_name')->get()
            : collect();
        $projectSuggestions = $this->findProjectSuggestions($availableProjects);
        $matchedProject = $projectSuggestions[0]['score'] >= self::MATCH_CONFIDENCE_THRESHOLD
            ? ($availableProjects->firstWhere('id', $projectSuggestions[0]['id']) ?? null)
            : null;

        if ($matchedProject) {
            $this->taskProjectMode = 'existing';
            $this->selectedProjectId = $matchedProject->id;
        } else {
            $this->taskProjectMode = 'new';
            $this->selectedProjectId = null;
        }
    }

    private function saveNoteResult(): void
    {
        $body = $this->buildDefaultNoteBody();

        if ($this->noteSaveMode === 'existing') {
            if (!$this->selectedNoteId) {
                throw new \RuntimeException('Pilih note existing atau ubah ke mode note baru.');
            }

            $note = Note::query()
                ->where('user_id', auth()->id() ?? 1)
                ->findOrFail($this->selectedNoteId);

            $mergedBody = trim(implode("\n\n", array_filter([
                trim((string) $note->body),
                '---',
                $body,
            ])));

            $note->update(['body' => $mergedBody]);
            return;
        }

        Note::create([
            'user_id' => auth()->id() ?? 1,
            'title' => trim($this->noteTitle) !== '' ? trim($this->noteTitle) : 'AI Capture '.now()->format('d M Y H:i'),
            'body' => $body,
            'sort_order' => ((int) Note::where('user_id', auth()->id() ?? 1)->max('sort_order')) + 1,
        ]);
    }

    private function historyPayload(string $status): array
    {
        return [
            'status' => $status,
            'label' => trim($this->historyLabel) !== '' ? trim($this->historyLabel) : null,
            'capture_mode' => $this->captureMode,
            'classification' => $this->classification,
            'summary' => $this->summary,
            'transcript' => $this->transcript,
            'client_name' => $this->clientName,
            'project_name' => $this->projectName,
            'note_title' => $this->noteTitle,
            'note_body' => $this->noteBody,
            'generated_tasks' => $this->generatedTasks,
            'generated_accounts' => $this->generatedAccounts,
            'save_tasks' => $this->saveTasks,
            'save_accounts' => $this->saveAccounts,
            'save_detail_note' => $this->saveDetailNote,
            'task_client_mode' => $this->taskClientMode,
            'selected_client_id' => $this->selectedClientId,
            'task_project_mode' => $this->taskProjectMode,
            'selected_project_id' => $this->selectedProjectId,
            'selected_account_folder_id' => $this->selectedAccountFolderId,
            'note_save_mode' => $this->noteSaveMode,
            'selected_note_id' => $this->selectedNoteId,
            'last_saved_at' => now(),
        ];
    }

    private function buildExportContent(string $format): string
    {
        $selectedTasks = array_values(array_filter($this->generatedTasks, fn($task) => !empty($task['selected']) && trim((string) ($task['name'] ?? '')) !== ''));
        $selectedAccounts = array_values(array_filter($this->generatedAccounts, fn($account) => !empty($account['selected']) && trim((string) ($account['platform'] ?? '')) !== ''));

        $lines = [
            $format === 'markdown' ? '# AI Capture Export' : 'AI CAPTURE EXPORT',
            $format === 'markdown' ? '' : '=================',
            'Label: '.($this->historyLabel !== '' ? $this->historyLabel : '-'),
            'Mode: '.$this->captureMode,
            'Classification: '.$this->classification,
            'Client: '.($this->clientName !== '' ? $this->clientName : '-'),
            'Project: '.($this->projectName !== '' ? $this->projectName : '-'),
            '',
            $format === 'markdown' ? '## Ringkasan' : 'RINGKASAN',
            $this->summary !== '' ? $this->summary : '-',
            '',
            $format === 'markdown' ? '## Tasks' : 'TASKS',
        ];

        if ($selectedTasks === []) {
            $lines[] = '- Tidak ada task terpilih';
        } else {
            foreach ($selectedTasks as $task) {
                $lines[] = '- '.($task['name'] ?? '').' ['.($task['status'] ?? 'todo').']';
                if (trim((string) ($task['detail'] ?? '')) !== '') {
                    $lines[] = '  Detail: '.trim((string) $task['detail']);
                }
                if (trim((string) ($task['due_date'] ?? '')) !== '') {
                    $lines[] = '  Due: '.trim((string) $task['due_date']);
                }
            }
        }

        $lines[] = '';
        $lines[] = $format === 'markdown' ? '## Accounts' : 'ACCOUNTS';

        if ($selectedAccounts === []) {
            $lines[] = '- Tidak ada account terpilih';
        } else {
            foreach ($selectedAccounts as $account) {
                $lines[] = '- '.($account['platform'] ?? '').' / '.($account['login_type'] ?? 'credentials');
                if (trim((string) ($account['username'] ?? '')) !== '') {
                    $lines[] = '  Username: '.trim((string) $account['username']);
                }
                if (trim((string) ($account['password'] ?? '')) !== '') {
                    $lines[] = '  Password: '.trim((string) $account['password']);
                }
                if (trim((string) ($account['detail'] ?? '')) !== '') {
                    $lines[] = '  Detail: '.trim((string) $account['detail']);
                }
            }
        }

        $lines[] = '';
        $lines[] = $format === 'markdown' ? '## Note' : 'NOTE';
        $lines[] = 'Title: '.($this->noteTitle !== '' ? $this->noteTitle : '-');
        $lines[] = $this->buildDefaultNoteBody() !== '' ? $this->buildDefaultNoteBody() : '-';
        $lines[] = '';
        $lines[] = $format === 'markdown' ? '## Transcript' : 'TRANSCRIPT';
        $lines[] = $this->transcript !== '' ? $this->transcript : '-';

        return implode("\n", $lines);
    }

    private function findClientSuggestions($clients): array
    {
        $needle = trim($this->clientName);
        if ($needle === '') {
            return [['id' => null, 'name' => '', 'score' => 0.0]];
        }

        $suggestions = $clients->map(function ($client) use ($needle) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'score' => $this->matchScore($needle, $client->name),
            ];
        })->sortByDesc('score')->take(3)->values()->all();

        return $suggestions !== [] ? $suggestions : [['id' => null, 'name' => '', 'score' => 0.0]];
    }

    private function findProjectSuggestions($projects): array
    {
        $needle = trim($this->projectName);
        if ($needle === '' || $projects->isEmpty()) {
            return [['id' => null, 'name' => '', 'score' => 0.0]];
        }

        $suggestions = $projects->map(function ($project) use ($needle) {
            return [
                'id' => $project->id,
                'name' => $project->project_name,
                'score' => $this->matchScore($needle, $project->project_name),
            ];
        })->sortByDesc('score')->take(3)->values()->all();

        return $suggestions !== [] ? $suggestions : [['id' => null, 'name' => '', 'score' => 0.0]];
    }

    private function matchScore(string $needle, string $candidate): float
    {
        $needle = Str::lower(trim($needle));
        $candidate = Str::lower(trim($candidate));

        if ($needle === '' || $candidate === '') {
            return 0.0;
        }

        if ($needle === $candidate) {
            return 100.0;
        }

        similar_text($needle, $candidate, $percent);

        if (str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
            $percent = max($percent, 90.0);
        }

        return round($percent, 2);
    }
}
