<?php

namespace App\Filament\Company\Pages\Finance;

use App\Filament\Company\Pages\Finance;
use App\Models\Accounting\FinanceEchance;
use App\Models\Accounting\FinanceEchanceSetting;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\RecurringInvoice;
use App\Models\Common\ParametrableInvoice;
use App\Models\Common\Ticket;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Echance extends Page
{
    protected static ?string $title = 'Echance';

    protected static ?string $navigationLabel = 'Echance';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationParentItem = 'Finance';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'finance/echance';

    protected static string $view = 'filament.company.pages.finance.echance';

    public ?string $initialBalanceInput = null;

    public string $manualEntryType = FinanceEchance::ENTRY_ENTREE;

    public ?string $manualAmount = null;

    public ?string $manualDate = null;

    public ?string $manualSupplier = null;

    public ?string $manualReference = null;

    public bool $manualIsPaid = false;

    public ?string $manualPaymentMethod = 'cash';

    /**
     * @var array<string, array<string, mixed>>
     */
    public array $sourceDrafts = [];

    public static function canAccess(): bool
    {
        return Finance::canAccess();
    }

    public function mount(): void
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            $this->initialBalanceInput = '0.000';

            return;
        }

        $balance = FinanceEchanceSetting::query()->firstOrCreate(
            ['company_id' => $companyId],
            ['initial_balance' => 0]
        );

        $this->initialBalanceInput = number_format((float) $balance->initial_balance, 3, '.', '');
        $this->manualDate = company_today()->toDateString();
    }

    public function saveInitialBalance(): void
    {
        $validated = $this->validate([
            'initialBalanceInput' => ['required', 'numeric'],
        ]);

        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return;
        }

        FinanceEchanceSetting::query()->updateOrCreate(
            ['company_id' => $companyId],
            ['initial_balance' => (float) $validated['initialBalanceInput']]
        );

        Notification::make()
            ->title('Solde initial mis a jour')
            ->success()
            ->send();
    }

    public function createEchance(string $sourceKey): void
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return;
        }

        $sourceInvoices = collect($this->buildSourceInvoices($companyId));
        $source = $sourceInvoices->firstWhere('key', $sourceKey);

        if (! $source) {
            Notification::make()
                ->title('Source introuvable')
                ->danger()
                ->send();

            return;
        }

        $draft = $this->sourceDrafts[$sourceKey] ?? [];

        $defaultEntryType = ($source['source_type'] ?? '') === 'invoice_archive'
            ? FinanceEchance::ENTRY_SORTIE
            : FinanceEchance::ENTRY_ENTREE;

        $entryType = (string) ($draft['entry_type'] ?? $defaultEntryType);
        $amount = (float) ($draft['amount'] ?? 0);
        $echanceDate = (string) ($draft['echance_date'] ?? company_today()->toDateString());
        $supplier = trim((string) ($draft['supplier'] ?? ($source['supplier'] ?? '')));

        if (! in_array($entryType, [FinanceEchance::ENTRY_ENTREE, FinanceEchance::ENTRY_SORTIE], true)) {
            $entryType = FinanceEchance::ENTRY_ENTREE;
        }

        FinanceEchance::query()->create([
            'company_id' => $companyId,
            'source_type' => $source['source_type'],
            'source_id' => $source['source_id'],
            'reference' => $source['reference'],
            'entry_type' => $entryType,
            'amount' => max(0, $amount),
            'echance_date' => $echanceDate,
            'supplier' => $supplier !== '' ? $supplier : null,
            'is_paid' => false,
        ]);

        Notification::make()
            ->title('Echance ajoutee')
            ->success()
            ->send();
    }

    public function setEntryType(int $echanceId, string $entryType): void
    {
        if (! in_array($entryType, [FinanceEchance::ENTRY_ENTREE, FinanceEchance::ENTRY_SORTIE], true)) {
            return;
        }

        FinanceEchance::query()
            ->whereKey($echanceId)
            ->update(['entry_type' => $entryType]);
    }

    public function setPaymentStatus(int $echanceId, bool $isPaid): void
    {
        $payload = ['is_paid' => $isPaid];

        if (! $isPaid) {
            $payload['payment_method'] = null;
        }

        FinanceEchance::query()
            ->whereKey($echanceId)
            ->update($payload);
    }

    public function setPaymentMethod(int $echanceId, string $method): void
    {
        if (! array_key_exists($method, $this->paymentMethodOptions())) {
            return;
        }

        FinanceEchance::query()
            ->whereKey($echanceId)
            ->where('is_paid', true)
            ->update(['payment_method' => $method]);
    }

    public function createManualEchance(): void
    {
        $validated = $this->validate([
            'manualEntryType' => ['required', 'in:' . FinanceEchance::ENTRY_ENTREE . ',' . FinanceEchance::ENTRY_SORTIE],
            'manualAmount' => ['required', 'numeric', 'min:0'],
            'manualDate' => ['required', 'date'],
            'manualSupplier' => ['nullable', 'string', 'max:255'],
            'manualReference' => ['nullable', 'string', 'max:255'],
            'manualIsPaid' => ['boolean'],
            'manualPaymentMethod' => ['nullable', 'in:' . implode(',', array_keys($this->paymentMethodOptions()))],
        ]);

        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return;
        }

        FinanceEchance::query()->create([
            'company_id' => $companyId,
            'source_type' => 'manual',
            'source_id' => null,
            'reference' => blank($validated['manualReference']) ? 'MANUAL' : $validated['manualReference'],
            'entry_type' => $validated['manualEntryType'],
            'amount' => (float) $validated['manualAmount'],
            'echance_date' => $validated['manualDate'],
            'supplier' => $validated['manualSupplier'] ?? null,
            'is_paid' => (bool) ($validated['manualIsPaid'] ?? false),
            'payment_method' => (bool) ($validated['manualIsPaid'] ?? false)
                ? (($validated['manualPaymentMethod'] ?? null) ?: 'cash')
                : null,
        ]);

        $this->manualEntryType = FinanceEchance::ENTRY_ENTREE;
        $this->manualAmount = null;
        $this->manualDate = company_today()->toDateString();
        $this->manualSupplier = null;
        $this->manualReference = null;
        $this->manualIsPaid = false;
        $this->manualPaymentMethod = 'cash';

        Notification::make()
            ->title('Operation manuelle ajoutee')
            ->success()
            ->send();
    }

    public function amountColorClass(FinanceEchance $echance): string
    {
        return $echance->entry_type === FinanceEchance::ENTRY_SORTIE
            ? 'text-rose-700'
            : 'text-emerald-700';
    }

    public function runningBalanceColorClass(float $value): string
    {
        return $value > 1 ? 'text-emerald-700' : 'text-rose-700';
    }

    /**
     * @return array<string, string>
     */
    public function paymentMethodOptions(): array
    {
        return [
            'cheque' => 'Cheque',
            'cash' => 'Cash',
            'virement' => 'Virement',
            'traite_bancaire' => 'Traite bancaire',
        ];
    }

    public function getViewData(): array
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return [
                'initialBalance' => 0,
                'currentBalance' => 0,
                'echances' => collect(),
                'sourceInvoices' => [],
            ];
        }

        $initialBalance = (float) FinanceEchanceSetting::query()->value('initial_balance');

        $echances = FinanceEchance::query()
            ->orderByRaw('COALESCE(echance_date, created_at) asc')
            ->orderBy('id')
            ->get();

        $runningBalance = $initialBalance;

        $echances = $echances->map(function (FinanceEchance $echance) use (&$runningBalance) {
            $amount = (float) $echance->amount;
            $runningBalance += $echance->entry_type === FinanceEchance::ENTRY_ENTREE ? $amount : -$amount;

            $echance->running_balance = $runningBalance;

            return $echance;
        });

        $sourceInvoices = $this->buildSourceInvoices($companyId);

        foreach ($sourceInvoices as $source) {
            $key = $source['key'];

            if (isset($this->sourceDrafts[$key])) {
                continue;
            }

            $defaultEntryType = ($source['source_type'] ?? '') === 'invoice_archive'
                ? FinanceEchance::ENTRY_SORTIE
                : FinanceEchance::ENTRY_ENTREE;

            $this->sourceDrafts[$key] = [
                'entry_type' => $defaultEntryType,
                'amount' => number_format((float) ($source['amount'] ?? 0), 3, '.', ''),
                'echance_date' => company_today()->toDateString(),
                'supplier' => $source['supplier'] ?? '',
            ];
        }

        return [
            'initialBalance' => $initialBalance,
            'currentBalance' => $runningBalance,
            'echances' => $echances,
            'sourceInvoices' => $sourceInvoices,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSourceInvoices(int $companyId): array
    {
        $rows = Collection::make();

        Invoice::query()
            ->where('company_id', $companyId)
            ->with('client:id,name')
            ->latest('date')
            ->limit(60)
            ->get()
            ->each(function (Invoice $invoice) use ($rows): void {
                $rows->push([
                    'key' => 'invoice:' . $invoice->id,
                    'source_type' => 'invoice',
                    'source_id' => $invoice->id,
                    'reference' => (string) ($invoice->invoice_number ?: ('INV-' . $invoice->id)),
                    'source_label' => 'Invoice',
                    'amount' => (float) $invoice->total,
                    'document_date' => optional($invoice->date)->toDateString(),
                    'supplier' => $invoice->client?->name,
                ]);
            });

        ParametrableInvoice::query()
            ->where('company_id', $companyId)
            ->latest('date')
            ->limit(60)
            ->get()
            ->each(function (ParametrableInvoice $invoice) use ($rows): void {
                $rows->push([
                    'key' => 'parametrable_invoice:' . $invoice->id,
                    'source_type' => 'parametrable_invoice',
                    'source_id' => $invoice->id,
                    'reference' => (string) ($invoice->invoice_number ?: ('PINV-' . $invoice->id)),
                    'source_label' => 'Parametrable Invoice',
                    'amount' => (float) $invoice->net_ht,
                    'document_date' => optional($invoice->date)->toDateString(),
                    'supplier' => $invoice->client_name,
                ]);
            });

        RecurringInvoice::query()
            ->where('company_id', $companyId)
            ->with('client:id,name')
            ->latest('updated_at')
            ->limit(60)
            ->get()
            ->each(function (RecurringInvoice $invoice) use ($rows): void {
                $rows->push([
                    'key' => 'recurring_invoice:' . $invoice->id,
                    'source_type' => 'recurring_invoice',
                    'source_id' => $invoice->id,
                    'reference' => 'RINV-' . $invoice->id,
                    'source_label' => 'Recurring Invoice',
                    'amount' => (float) $invoice->total,
                    'document_date' => optional($invoice->next_date ?? $invoice->start_date)->toDateString(),
                    'supplier' => $invoice->client?->name,
                ]);
            });

        Ticket::query()
            ->where('company_id', $companyId)
            ->whereNotNull('invoice_file')
            ->latest('invoice_date')
            ->limit(60)
            ->get()
            ->each(function (Ticket $ticket) use ($rows): void {
                $rows->push([
                    'key' => 'invoice_archive:' . $ticket->id,
                    'source_type' => 'invoice_archive',
                    'source_id' => $ticket->id,
                    'reference' => (string) ($ticket->name ?: ('ARCH-' . $ticket->id)),
                    'source_label' => 'Invoice Archive',
                    'amount' => (float) ($ticket->invoice_amount ?? 0),
                    'document_date' => optional($ticket->invoice_date)->toDateString(),
                    'supplier' => $ticket->invoice_from,
                ]);
            });

        return $rows
            ->sortByDesc(fn (array $row): string => (string) ($row['document_date'] ?? '0000-00-00'))
            ->values()
            ->all();
    }
}
