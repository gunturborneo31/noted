<?php

namespace App\Livewire;

use App\Models\CashflowEntry;
use App\Models\CashflowPerson;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CashflowTracker extends Component
{
    use WithPagination;

    #[Url]
    public ?int $selectedPersonId = null;

    public bool $showNewPersonForm = false;
    public string $newPersonName = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $summaryGroupBy = 'day';

    public int $perPage = 10;

    public array $entryRows = [];

    public ?int $editingEntryId = null;
    public string $editEntryType = 'masuk';
    public string $editPrice = '0';
    public int $editQuantity = 1;
    public string $editTotal = '0.00';
    public string $editDescription = '';
    public string $editEntryDate = '';
    public bool $managePeopleMode = false;
    public ?int $editingPersonId = null;
    public string $editingPersonName = '';

    public function mount(): void
    {
        if (!$this->selectedPersonId) {
            $this->selectedPersonId = CashflowPerson::orderBy('sort_order')->orderBy('name')->value('id');
        }

        $this->entryRows = [$this->emptyRow()];
    }

    public function updatedSelectedPersonId($value): void
    {
        $this->selectedPersonId = $value ? (int) $value : null;
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage($value): void
    {
        $allowed = [5, 10, 25, 50, 100];
        $this->perPage = in_array((int) $value, $allowed, true) ? (int) $value : 10;
        $this->resetPage();
    }

    public function updatedSummaryGroupBy($value): void
    {
        if (!in_array($value, ['day', 'month'], true)) {
            $this->summaryGroupBy = 'day';
        }
    }

    public function addRow(): void
    {
        if (!$this->selectedPersonId) {
            return;
        }

        $this->entryRows[] = $this->emptyRow();
    }

    public function removeRow(int $index): void
    {
        if (!isset($this->entryRows[$index])) {
            return;
        }

        unset($this->entryRows[$index]);
        $this->entryRows = array_values($this->entryRows);

        if ($this->entryRows === []) {
            $this->entryRows = [$this->emptyRow()];
        }
    }

    public function updatedEntryRows($value = null, $key = null): void
    {
        if (!is_string($key)) {
            return;
        }

        $segments = explode('.', $key);
        $rowIndex = isset($segments[0]) ? (int) $segments[0] : -1;

        if (!isset($this->entryRows[$rowIndex])) {
            return;
        }

        $price = $this->normalizeMoneyInput($this->entryRows[$rowIndex]['price'] ?? 0);
        $quantity = max(0, (int) ($this->entryRows[$rowIndex]['quantity'] ?? 0));

        if (str_ends_with($key, '.price')) {
            $this->entryRows[$rowIndex]['price'] = $this->formatMoneyInput($price);
        }

        $this->entryRows[$rowIndex]['total'] = $this->formatMoneyInput($price * $quantity);
    }

    public function createPerson(): void
    {
        $validated = $this->validate([
            'newPersonName' => 'required|string|max:255',
        ]);

        $person = CashflowPerson::create([
            'name' => trim($validated['newPersonName']),
            'sort_order' => ((int) CashflowPerson::max('sort_order')) + 1,
        ]);

        $this->newPersonName = '';
        $this->showNewPersonForm = false;
        $this->selectedPersonId = $person->id;
        $this->resetPage();
    }

    public function toggleManagePeopleMode(): void
    {
        $this->managePeopleMode = !$this->managePeopleMode;

        if (!$this->managePeopleMode) {
            $this->cancelEditPerson();
        }
    }

    public function startEditPerson(int $personId): void
    {
        $person = CashflowPerson::findOrFail($personId);
        $this->editingPersonId = $person->id;
        $this->editingPersonName = (string) $person->name;
    }

    public function savePersonName(): void
    {
        if (!$this->editingPersonId) {
            return;
        }

        $validated = $this->validate([
            'editingPersonName' => 'required|string|max:255',
        ]);

        CashflowPerson::where('id', $this->editingPersonId)->update([
            'name' => trim($validated['editingPersonName']),
        ]);

        $this->cancelEditPerson();
        session()->flash('success', 'Nama orang berhasil diperbarui.');
    }

    public function cancelEditPerson(): void
    {
        $this->editingPersonId = null;
        $this->editingPersonName = '';
    }

    public function selectPerson(int $personId): void
    {
        $this->selectedPersonId = $personId;
        $this->cancelEditPerson();
        $this->cancelEditEntry();
        $this->resetPage();
    }

    public function deletePerson(int $personId): void
    {
        if ($this->editingPersonId === $personId) {
            $this->cancelEditPerson();
        }

        CashflowPerson::where('id', $personId)->delete();

        if ($this->selectedPersonId === $personId) {
            $this->selectedPersonId = CashflowPerson::orderBy('sort_order')->orderBy('name')->value('id');
        }

        $this->cancelEditEntry();
        $this->resetPage();
        session()->flash('success', 'Orang berhasil dihapus beserta seluruh cashflow-nya.');
    }

    public function saveEntries(): void
    {
        $normalizedRows = collect($this->entryRows)->map(function (array $row): array {
            $price = $this->normalizeMoneyInput($row['price'] ?? 0);
            $quantity = max(0, (int) ($row['quantity'] ?? 0));

            return [
                'entry_type' => (string) ($row['entry_type'] ?? 'masuk'),
                'price' => $price,
                'quantity' => $quantity,
                'description' => trim((string) ($row['description'] ?? '')),
                'entry_date' => trim((string) ($row['entry_date'] ?? '')),
            ];
        })->values()->all();

        foreach ($normalizedRows as $index => $row) {
            $this->entryRows[$index]['price'] = $this->formatMoneyInput((int) $row['price']);
            $this->entryRows[$index]['total'] = $this->formatMoneyInput(((int) $row['price']) * ((int) $row['quantity']));
        }

        $validated = Validator::make([
            'selectedPersonId' => $this->selectedPersonId,
            'entryRows' => $normalizedRows,
        ], [
            'selectedPersonId' => 'required|exists:cashflow_people,id',
            'entryRows' => 'required|array|min:1',
            'entryRows.*.entry_type' => 'required|in:masuk,keluar',
            'entryRows.*.price' => 'required|integer|min:0',
            'entryRows.*.quantity' => 'required|integer|min:1',
            'entryRows.*.description' => 'nullable|string|max:255',
            'entryRows.*.entry_date' => 'nullable|date',
        ])->validate();

        $created = 0;

        foreach ($validated['entryRows'] as $row) {
            $price = (int) $row['price'];
            $quantity = (int) $row['quantity'];

            if ($price <= 0 || $quantity <= 0) {
                continue;
            }

            CashflowEntry::create([
                'person_id' => $this->selectedPersonId,
                'entry_type' => $row['entry_type'],
                'price' => $price,
                'quantity' => $quantity,
                'total' => $price * $quantity,
                'description' => $row['description'] !== '' ? $row['description'] : null,
                'entry_date' => $row['entry_date'] !== '' ? $row['entry_date'] : null,
            ]);

            $created++;
        }

        $this->entryRows = [$this->emptyRow()];
        session()->flash('success', $created > 0 ? $created.' catatan keuangan berhasil disimpan.' : 'Tidak ada catatan valid untuk disimpan.');
    }

    public function deleteEntry(int $entryId): void
    {
        if ($this->editingEntryId === $entryId) {
            $this->cancelEditEntry();
        }

        CashflowEntry::where('id', $entryId)->delete();
        session()->flash('success', 'Catatan keuangan berhasil dihapus.');
    }

    public function startEditEntry(int $entryId): void
    {
        $entry = CashflowEntry::where('person_id', $this->selectedPersonId)->findOrFail($entryId);

        $this->editingEntryId = $entry->id;
        $this->editEntryType = $entry->entry_type;
        $this->editPrice = $this->formatMoneyInput((int) $entry->price);
        $this->editQuantity = (int) $entry->quantity;
        $this->editTotal = $this->formatMoneyInput((int) $entry->total);
        $this->editDescription = (string) ($entry->description ?? '');
        $this->editEntryDate = $entry->entry_date instanceof CarbonInterface
            ? $entry->entry_date->format('Y-m-d')
            : '';
    }

    public function updatedEditPrice(): void
    {
        $this->editPrice = $this->formatMoneyInput($this->normalizeMoneyInput($this->editPrice));
        $this->recalculateEditTotal();
    }

    public function updatedEditQuantity(): void
    {
        $this->recalculateEditTotal();
    }

    public function saveEditEntry(): void
    {
        if (!$this->editingEntryId) {
            return;
        }

        $normalizedEditPrice = $this->normalizeMoneyInput($this->editPrice);
        $this->editPrice = $this->formatMoneyInput($normalizedEditPrice);

        $validated = Validator::make([
            'editEntryType' => $this->editEntryType,
            'editPrice' => $normalizedEditPrice,
            'editQuantity' => $this->editQuantity,
            'editDescription' => trim((string) $this->editDescription),
            'editEntryDate' => trim((string) $this->editEntryDate),
        ], [
            'editEntryType' => 'required|in:masuk,keluar',
            'editPrice' => 'required|integer|min:0',
            'editQuantity' => 'required|integer|min:1',
            'editDescription' => 'nullable|string|max:255',
            'editEntryDate' => 'nullable|date',
        ])->validate();

        $price = (int) $validated['editPrice'];
        $quantity = (int) $validated['editQuantity'];

        CashflowEntry::where('id', $this->editingEntryId)->update([
            'entry_type' => $validated['editEntryType'],
            'price' => $price,
            'quantity' => $quantity,
            'total' => $price * $quantity,
            'description' => trim((string) $validated['editDescription']) ?: null,
            'entry_date' => trim((string) $validated['editEntryDate']) ?: null,
        ]);

        $this->cancelEditEntry();
        session()->flash('success', 'Catatan keuangan berhasil diperbarui.');
    }

    public function cancelEditEntry(): void
    {
        $this->editingEntryId = null;
        $this->editEntryType = 'masuk';
        $this->editPrice = '0';
        $this->editQuantity = 1;
        $this->editTotal = '0';
        $this->editDescription = '';
        $this->editEntryDate = '';
    }

    public function render()
    {
        $people = CashflowPerson::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $peopleSummary = CashflowEntry::query()
            ->select('person_id')
            ->selectRaw("SUM(CASE WHEN entry_type = 'masuk' THEN total ELSE 0 END) as income_total")
            ->selectRaw("SUM(CASE WHEN entry_type = 'keluar' THEN total ELSE 0 END) as expense_total")
            ->groupBy('person_id')
            ->get()
            ->keyBy('person_id');

        $people = $people->map(function (CashflowPerson $person) use ($peopleSummary) {
            $summary = $peopleSummary->get($person->id);
            $income = (float) ($summary->income_total ?? 0);
            $expense = (float) ($summary->expense_total ?? 0);

            $person->income_total = $income;
            $person->expense_total = $expense;
            $person->balance_total = $income - $expense;

            return $person;
        });

        $entries = CashflowEntry::query()
            ->when($this->selectedPersonId, fn($query) => $query->where('person_id', $this->selectedPersonId))
            ->when($this->dateFrom !== '', fn($query) => $query->whereDate('entry_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn($query) => $query->whereDate('entry_date', '<=', $this->dateTo))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $summaryQuery = CashflowEntry::query()
            ->when($this->selectedPersonId, fn($query) => $query->where('person_id', $this->selectedPersonId))
            ->when($this->dateFrom !== '', fn($query) => $query->whereDate('entry_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn($query) => $query->whereDate('entry_date', '<=', $this->dateTo));

        $income = (float) (clone $summaryQuery)->where('entry_type', 'masuk')->sum('total');
        $expense = (float) (clone $summaryQuery)->where('entry_type', 'keluar')->sum('total');
        $balance = $income - $expense;

        $periodExpression = $this->summaryGroupBy === 'month'
            ? "strftime('%Y-%m', coalesce(entry_date, created_at))"
            : "date(coalesce(entry_date, created_at))";

        $groupSummaries = (clone $summaryQuery)
            ->selectRaw($periodExpression.' as period_key')
            ->selectRaw("SUM(CASE WHEN entry_type = 'masuk' THEN total ELSE 0 END) as income_total")
            ->selectRaw("SUM(CASE WHEN entry_type = 'keluar' THEN total ELSE 0 END) as expense_total")
            ->groupBy('period_key')
            ->orderByDesc('period_key')
            ->get()
            ->map(function ($row) {
                $incomeTotal = (float) $row->income_total;
                $expenseTotal = (float) $row->expense_total;

                return [
                    'period_key' => (string) $row->period_key,
                    'income_total' => $incomeTotal,
                    'expense_total' => $expenseTotal,
                    'balance_total' => $incomeTotal - $expenseTotal,
                ];
            });

        $recapRows = CashflowEntry::query()
            ->join('cashflow_people', 'cashflow_people.id', '=', 'cashflow_entries.person_id')
            ->when($this->selectedPersonId, fn($query) => $query->where('cashflow_entries.person_id', $this->selectedPersonId))
            ->when($this->dateFrom !== '', fn($query) => $query->whereDate('cashflow_entries.entry_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn($query) => $query->whereDate('cashflow_entries.entry_date', '<=', $this->dateTo))
            ->orderBy('cashflow_people.sort_order')
            ->orderBy('cashflow_people.name')
            ->orderByDesc('cashflow_entries.entry_date')
            ->orderByDesc('cashflow_entries.id')
            ->get([
                'cashflow_entries.id',
                'cashflow_entries.entry_type',
                'cashflow_entries.quantity',
                'cashflow_entries.total',
                'cashflow_entries.description',
                'cashflow_entries.entry_date',
                'cashflow_people.id as person_id',
                'cashflow_people.name as person_name',
            ]);

        $recapLines = ['REKAP CASHFLOW'];

        if ($this->dateFrom !== '' || $this->dateTo !== '') {
            $fromLabel = $this->dateFrom !== '' ? $this->dateFrom : '-';
            $toLabel = $this->dateTo !== '' ? $this->dateTo : '-';
            $recapLines[] = 'Periode: '.$fromLabel.' s/d '.$toLabel;
        }

        $recapLines[] = '';

        $grandQuantity = 0;
        $grandTotal = 0.0;

        $groupedRecapRows = $recapRows->groupBy('person_id');

        foreach ($groupedRecapRows as $personRows) {
            $personName = (string) ($personRows->first()->person_name ?? '-');
            $personQuantity = (int) $personRows->sum('quantity');
            $personTotal = (float) $personRows->sum('total');

            $recapLines[] = 'Nama: '.$personName;

            foreach ($personRows as $index => $row) {
                $label = trim((string) ($row->description ?? ''));
                if ($label === '') {
                    $label = ucfirst((string) $row->entry_type).' #'.$row->id;
                }

                $entryDate = $row->entry_date instanceof CarbonInterface
                    ? $row->entry_date->format('d-m-Y')
                    : '-';

                $recapLines[] = ($index + 1).'. '.$label;
                $recapLines[] = '   Tanggal: '.$entryDate;
                $recapLines[] = '   Jumlah: '.(int) $row->quantity;
                $recapLines[] = '   Total: Rp '.number_format((float) $row->total, 0, ',', '.');
            }

            $recapLines[] = 'Subtotal '.$personName.':' ;
            $recapLines[] = '- Jumlah: '.$personQuantity;
            $recapLines[] = '- Total: Rp '.number_format($personTotal, 0, ',', '.');
            $recapLines[] = '';

            $grandQuantity += $personQuantity;
            $grandTotal += $personTotal;
        }

        if ($groupedRecapRows->isEmpty()) {
            $recapLines[] = 'Belum ada data cashflow pada filter saat ini.';
            $recapLines[] = '';
        }

        $recapLines[] = 'TOTAL KESELURUHAN';
        $recapLines[] = '- Jumlah: '.$grandQuantity;
        $recapLines[] = '- Total: Rp '.number_format($grandTotal, 0, ',', '.');

        $recapText = implode(PHP_EOL, $recapLines);

        return view('livewire.cashflow-tracker', [
            'people' => $people,
            'entries' => $entries,
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'groupSummaries' => $groupSummaries,
            'selectedPerson' => $this->selectedPersonId ? $people->firstWhere('id', $this->selectedPersonId) : null,
            'recapText' => $recapText,
        ]);
    }

    private function emptyRow(): array
    {
        return [
            'entry_type' => 'masuk',
            'price' => '0',
            'quantity' => 1,
            'total' => '0',
            'description' => '',
            'entry_date' => now()->format('Y-m-d'),
        ];
    }

    private function recalculateEditTotal(): void
    {
        $price = $this->normalizeMoneyInput($this->editPrice);
        $quantity = max(0, (int) $this->editQuantity);
        $this->editTotal = $this->formatMoneyInput($price * $quantity);
    }

    private function normalizeMoneyInput(mixed $value): int
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        return $digits !== '' ? (int) $digits : 0;
    }

    private function formatMoneyInput(int $value): string
    {
        return number_format(max(0, $value), 0, ',', '.');
    }
}
