<?php

namespace App\Livewire;

use App\Models\AccountEntry;
use App\Models\AccountFolder;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class AccountDirectory extends Component
{
    public string $folderName = '';
    public ?int $parentFolderId = null;
    public ?int $selectedFolderId = null;

    public array $accountRows = [];

    public ?int $editingAccountId = null;
    public string $editPlatform = '';
    public string $editLoginType = 'credentials';
    public string $editUsername = '';
    public string $editPassword = '';

    private const LOGIN_TYPES = ['credentials', 'google', 'email'];

    public function mount(): void
    {
        $firstFolder = AccountFolder::orderBy('sort_order')->orderBy('name')->first();
        $this->selectedFolderId = $firstFolder?->id;
        $this->accountRows = [$this->emptyRow()];
    }

    public function selectFolder(int $folderId): void
    {
        $this->selectedFolderId = $folderId;
    }

    public function createFolder(): void
    {
        $data = $this->validate([
            'folderName' => 'required|string|max:255',
            'parentFolderId' => 'nullable|exists:account_folders,id',
        ]);

        $folder = AccountFolder::create([
            'name' => trim($data['folderName']),
            'parent_id' => $data['parentFolderId'] ?: null,
            'sort_order' => ((int) AccountFolder::when($data['parentFolderId'], fn($q) => $q->where('parent_id', $data['parentFolderId']))
                ->when(!$data['parentFolderId'], fn($q) => $q->whereNull('parent_id'))
                ->max('sort_order')) + 1,
        ]);

        $this->folderName = '';
        $this->parentFolderId = null;
        $this->selectedFolderId = $folder->id;

        session()->flash('success', 'Folder berhasil dibuat.');
    }

    public function addRow(): void
    {
        $this->accountRows[] = $this->emptyRow();
    }

    public function removeRow(int $index): void
    {
        if (!isset($this->accountRows[$index])) {
            return;
        }

        unset($this->accountRows[$index]);
        $this->accountRows = array_values($this->accountRows);

        if (count($this->accountRows) === 0) {
            $this->accountRows[] = $this->emptyRow();
        }
    }

    public function updatedAccountRows($value, $key = null): void
    {
        if (!is_string($key)) {
            return;
        }

        if (!str_ends_with($key, '.login_type')) {
            return;
        }

        $segments = explode('.', $key);
        $rowIndex = (int) ($segments[0] ?? -1);

        if (!isset($this->accountRows[$rowIndex])) {
            return;
        }

        if (($this->accountRows[$rowIndex]['login_type'] ?? 'credentials') !== 'credentials') {
            $this->accountRows[$rowIndex]['username'] = '';
            $this->accountRows[$rowIndex]['password'] = '';
        }
    }

    public function saveAccounts(): void
    {
        $validated = $this->validate([
            'selectedFolderId' => 'required|exists:account_folders,id',
            'accountRows' => 'required|array|min:1',
            'accountRows.*.platform' => 'required|string|max:255',
            'accountRows.*.username' => 'nullable|string|max:255',
            'accountRows.*.password' => 'nullable|string|max:255',
            'accountRows.*.login_type' => 'required|in:credentials,google,email',
        ]);

        $created = 0;

        foreach ($validated['accountRows'] as $row) {
            $platform = trim((string) $row['platform']);
            $loginType = trim((string) $row['login_type']);
            $username = trim((string) ($row['username'] ?? ''));
            $password = trim((string) ($row['password'] ?? ''));

            if (!in_array($loginType, self::LOGIN_TYPES, true) || $platform === '') {
                continue;
            }

            if ($loginType === 'credentials' && ($username === '' || $password === '')) {
                continue;
            }

            $accountValue = $loginType === 'credentials'
                ? $username
                : 'Masuk dengan '.ucfirst($loginType);

            AccountEntry::create([
                'folder_id' => $this->selectedFolderId,
                'platform' => $platform,
                'account_value' => $accountValue,
                'username' => $loginType === 'credentials' ? $username : null,
                'password' => $loginType === 'credentials' ? $password : null,
                'login_type' => $loginType,
            ]);

            $created++;
        }

        $this->accountRows = [$this->emptyRow()];
        session()->flash('success', $created > 0 ? $created.' akun berhasil ditambahkan.' : 'Tidak ada akun valid untuk disimpan.');
    }

    public function deleteAccount(int $accountId): void
    {
        AccountEntry::where('id', $accountId)->delete();

        if ($this->editingAccountId === $accountId) {
            $this->cancelEditAccount();
        }

        session()->flash('success', 'Akun berhasil dihapus.');
    }

    public function startEditAccount(int $accountId): void
    {
        $account = AccountEntry::findOrFail($accountId);

        $this->editingAccountId = $account->id;
        $this->editPlatform = $account->platform;
        $this->editLoginType = $account->login_type ?? 'credentials';
        $this->editUsername = $account->username ?: $account->account_value;
        $this->editPassword = $account->password ?? '';
    }

    public function cancelEditAccount(): void
    {
        $this->editingAccountId = null;
        $this->editPlatform = '';
        $this->editLoginType = 'credentials';
        $this->editUsername = '';
        $this->editPassword = '';
    }

    public function updatedEditLoginType(): void
    {
        if ($this->editLoginType !== 'credentials') {
            $this->editUsername = '';
            $this->editPassword = '';
        }
    }

    public function saveEditAccount(): void
    {
        if (!$this->editingAccountId) {
            return;
        }

        $validated = $this->validate([
            'editPlatform' => 'required|string|max:255',
            'editLoginType' => 'required|in:credentials,google,email',
            'editUsername' => 'nullable|string|max:255',
            'editPassword' => 'nullable|string|max:255',
        ]);

        if ($validated['editLoginType'] === 'credentials' &&
            (trim($validated['editUsername']) === '' || trim($validated['editPassword']) === '')) {
            session()->flash('success', 'Username dan password wajib diisi untuk mode Username/Password.');
            return;
        }

        $account = AccountEntry::findOrFail($this->editingAccountId);

        $account->update([
            'platform' => trim($validated['editPlatform']),
            'login_type' => $validated['editLoginType'],
            'username' => $validated['editLoginType'] === 'credentials' ? trim($validated['editUsername']) : null,
            'password' => $validated['editLoginType'] === 'credentials' ? trim($validated['editPassword']) : null,
            'account_value' => $validated['editLoginType'] === 'credentials'
                ? trim($validated['editUsername'])
                : 'Masuk dengan '.ucfirst($validated['editLoginType']),
        ]);

        $this->cancelEditAccount();
        session()->flash('success', 'Akun berhasil diperbarui.');
    }

    public function moveFolderUp(int $folderId): void
    {
        $this->moveFolder($folderId, -1);
    }

    public function moveFolderDown(int $folderId): void
    {
        $this->moveFolder($folderId, 1);
    }

    public function reorderFolders($parentId, array $orderedIds): void
    {
        $normalizedParentId = $parentId === null || $parentId === '' ? null : (int) $parentId;
        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));

        $siblings = AccountFolder::query()
            ->when($normalizedParentId !== null, fn($q) => $q->where('parent_id', $normalizedParentId))
            ->when($normalizedParentId === null, fn($q) => $q->whereNull('parent_id'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        if (count($siblings) < 2 || count($orderedIds) !== count($siblings)) {
            return;
        }

        $expected = $siblings;
        sort($expected);
        $received = $orderedIds;
        sort($received);

        if ($expected !== $received) {
            return;
        }

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $folderId) {
                AccountFolder::where('id', $folderId)->update(['sort_order' => $index + 1]);
            }
        });
    }

    public function render()
    {
        $folderTree = AccountFolder::with(['children.children', 'accounts'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $allFolders = AccountFolder::with('parent')->orderBy('sort_order')->orderBy('name')->get();

        $selectedFolder = $this->selectedFolderId
            ? AccountFolder::with(['parent.parent', 'accounts'])->find($this->selectedFolderId)
            : null;

        $allAccounts = AccountEntry::with(['folder.parent.parent'])
            ->latest()
            ->get();

        return view('livewire.account-directory', compact('folderTree', 'allFolders', 'selectedFolder', 'allAccounts'));
    }

    private function moveFolder(int $folderId, int $direction): void
    {
        $folder = AccountFolder::findOrFail($folderId);

        $siblings = AccountFolder::query()
            ->when($folder->parent_id, fn($q) => $q->where('parent_id', $folder->parent_id))
            ->when(!$folder->parent_id, fn($q) => $q->whereNull('parent_id'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order'])
            ->values();

        if ($siblings->count() < 2) {
            return;
        }

        foreach ($siblings as $index => $row) {
            AccountFolder::where('id', $row->id)->update(['sort_order' => $index + 1]);
        }

        $siblings = AccountFolder::query()
            ->when($folder->parent_id, fn($q) => $q->where('parent_id', $folder->parent_id))
            ->when(!$folder->parent_id, fn($q) => $q->whereNull('parent_id'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order'])
            ->values();

        $currentIndex = $siblings->search(fn($row) => (int) $row->id === $folderId);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $currentIndex + $direction;
        if ($targetIndex < 0 || $targetIndex >= $siblings->count()) {
            return;
        }

        DB::transaction(function () use ($siblings, $currentIndex, $targetIndex): void {
            $current = $siblings[$currentIndex];
            $target = $siblings[$targetIndex];

            AccountFolder::where('id', $current->id)->update(['sort_order' => $target->sort_order]);
            AccountFolder::where('id', $target->id)->update(['sort_order' => $current->sort_order]);
        });
    }

    private function emptyRow(): array
    {
        return [
            'platform' => 'IG',
            'username' => '',
            'password' => '',
            'login_type' => 'credentials',
        ];
    }
}
