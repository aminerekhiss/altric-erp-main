<?php

namespace App\Filament\Company\Pages\Finance;

use App\Filament\Company\Pages\Finance;
use App\Models\Accounting\FinanceComptabiliteTable;
use App\Models\Accounting\FinanceEchance;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TableauComptabilite extends Page
{
    protected static ?string $title = 'Tableau comptabilite';

    protected static ?string $navigationLabel = 'Tableau comptabilite';

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationParentItem = 'Echance';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'finance/echance/tableau-comptabilite';

    protected static string $view = 'filament.company.pages.finance.tableau-comptabilite';

    /**
     * @var array<string, string>
     */
    public array $initialBalances = [];

    public static function canAccess(): bool
    {
        return Finance::canAccess();
    }

    public function mount(): void
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return;
        }

        foreach (array_keys($this->paymentMethodDefinitions()) as $method) {
            $setting = FinanceComptabiliteTable::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'payment_method' => $method,
                ],
                [
                    'initial_balance' => 0,
                ]
            );

            $this->initialBalances[$method] = number_format((float) $setting->initial_balance, 3, '.', '');
        }
    }

    public function saveInitialBalance(string $method): void
    {
        if (! array_key_exists($method, $this->paymentMethodDefinitions())) {
            return;
        }

        $this->validate([
            "initialBalances.{$method}" => ['required', 'numeric'],
        ]);

        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return;
        }

        FinanceComptabiliteTable::query()->updateOrCreate(
            [
                'company_id' => $companyId,
                'payment_method' => $method,
            ],
            [
                'initial_balance' => (float) ($this->initialBalances[$method] ?? 0),
            ]
        );

        Notification::make()
            ->title('Solde initial mis a jour')
            ->success()
            ->send();
    }

    public function getViewData(): array
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return [
                'methodTables' => [],
            ];
        }

        $tables = [];

        foreach ($this->paymentMethodDefinitions() as $method => $def) {
            $rows = FinanceEchance::query()
                ->where('company_id', $companyId)
                ->where('is_paid', true)
                ->where('payment_method', $method)
                ->orderByRaw('COALESCE(echance_date, created_at) asc')
                ->orderBy('id')
                ->get();

            $debitTotal = 0.0;
            $creditTotal = 0.0;

            foreach ($rows as $row) {
                $amount = (float) $row->amount;

                if ($row->entry_type === FinanceEchance::ENTRY_ENTREE) {
                    $debitTotal += $amount;
                } else {
                    $creditTotal += $amount;
                }
            }

            $initial = (float) ($this->initialBalances[$method] ?? 0);
            $result = $initial + $debitTotal - $creditTotal;

            $tables[] = [
                'method' => $method,
                'name' => $def['name'],
                'code' => $def['code'],
                'rows' => $rows,
                'initial' => $initial,
                'debit_total' => $debitTotal,
                'credit_total' => $creditTotal,
                'result' => $result,
            ];
        }

        return [
            'methodTables' => $tables,
        ];
    }

    /**
     * @return array<string, array{name: string, code: string}>
     */
    private function paymentMethodDefinitions(): array
    {
        return [
            'cheque' => ['name' => 'Cheque', 'code' => 'CHQ'],
            'cash' => ['name' => 'Cash', 'code' => 'CASH'],
            'virement' => ['name' => 'Virement', 'code' => 'VIR'],
            'traite_bancaire' => ['name' => 'Traite bancaire', 'code' => 'TRB'],
        ];
    }
}
