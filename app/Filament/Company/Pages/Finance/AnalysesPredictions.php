<?php

namespace App\Filament\Company\Pages\Finance;

use App\Filament\Company\Pages\Finance;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Transaction;
use App\Models\Common\Client;
use App\Services\GrokInsightsService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AnalysesPredictions extends Page
{
    protected static ?string $title = 'Analyses et Predictions';

    protected static ?string $navigationLabel = 'Analyses et Predictions';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationParentItem = 'Finance';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'finance/analyses-predictions';

    protected static string $view = 'filament.company.pages.finance.analyses-predictions';

    public ?string $aiInsights = null;

    public static function canAccess(): bool
    {
        return Finance::canAccess();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateInsights')
                ->label('Generer Insights IA')
                ->icon('heroicon-o-sparkles')
                ->action(fn () => $this->generateInsights()),
        ];
    }

    public function generateInsights(): void
    {
        try {
            $data = $this->getViewData();

            $prompt = implode("\n", [
                'Analyse financiere ERP: fournir recommandations concretes en francais.',
                'KPI mois courant:',
                '- Ventes: ' . $data['kpis']['current_month_sales'],
                '- Achats: ' . $data['kpis']['current_month_purchases'],
                '- Cashflow net: ' . $data['kpis']['current_month_cashflow'],
                '- Clients actifs: ' . $data['kpis']['active_clients'],
                '- Taux encaissement: ' . $data['kpis']['collection_rate'] . '%',
                'Predictions 3 mois ventes: ' . implode(', ', array_map(fn ($item) => $item['value'], $data['salesPredictions'])),
                'Predictions 3 mois achats: ' . implode(', ', array_map(fn ($item) => $item['value'], $data['purchasePredictions'])),
                'Donner: 1) diagnostic 2) risques 3) actions prioritaires.',
            ]);

            $this->aiInsights = app(GrokInsightsService::class)->generateInsights($prompt);

            Notification::make()
                ->success()
                ->title('Analyse IA generee')
                ->body('Insights mis a jour avec succes.')
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('Echec de l\'analyse IA')
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function getViewData(): array
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return [
                'kpis' => [
                    'current_month_sales' => 0,
                    'current_month_purchases' => 0,
                    'current_month_cashflow' => 0,
                    'active_clients' => 0,
                    'collection_rate' => 0,
                ],
                'salesChart' => ['labels' => [], 'values' => []],
                'purchaseChart' => ['labels' => [], 'values' => []],
                'cashflowChart' => ['labels' => [], 'values' => []],
                'salesPredictions' => [],
                'purchasePredictions' => [],
                'cashflowPredictions' => [],
                'aiInsights' => $this->aiInsights,
            ];
        }

        $months = collect(range(11, 0))
            ->map(fn (int $offset) => company_today()->copy()->startOfMonth()->subMonths($offset))
            ->values();

        $monthKeys = $months->map(fn ($date) => $date->format('Y-m'))->all();
        $labels = $months->map(fn ($date) => $date->format('M Y'))->all();

        $salesRaw = Invoice::query()
            ->where('company_id', $companyId)
            ->whereBetween('date', [$months->first()->toDateString(), $months->last()->copy()->endOfMonth()->toDateString()])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month_key")
            ->selectRaw('COALESCE(SUM(total), 0) as total_amount')
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key')
            ->all();

        $purchasesRaw = Bill::query()
            ->where('company_id', $companyId)
            ->whereBetween('date', [$months->first()->toDateString(), $months->last()->copy()->endOfMonth()->toDateString()])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month_key")
            ->selectRaw('COALESCE(SUM(total), 0) as total_amount')
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key')
            ->all();

        $depositsRaw = Transaction::query()
            ->where('company_id', $companyId)
            ->where('type', 'deposit')
            ->whereBetween('posted_at', [$months->first()->toDateString(), $months->last()->copy()->endOfMonth()->toDateString()])
            ->selectRaw("DATE_FORMAT(posted_at, '%Y-%m') as month_key")
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key')
            ->all();

        $withdrawalsRaw = Transaction::query()
            ->where('company_id', $companyId)
            ->where('type', 'withdrawal')
            ->whereBetween('posted_at', [$months->first()->toDateString(), $months->last()->copy()->endOfMonth()->toDateString()])
            ->selectRaw("DATE_FORMAT(posted_at, '%Y-%m') as month_key")
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key')
            ->all();

        $salesSeries = collect($monthKeys)->map(fn (string $key) => round((float) ($salesRaw[$key] ?? 0), 3))->all();
        $purchaseSeries = collect($monthKeys)->map(fn (string $key) => round((float) ($purchasesRaw[$key] ?? 0), 3))->all();
        $cashflowSeries = collect($monthKeys)->map(fn (string $key) => round((float) (($depositsRaw[$key] ?? 0) - ($withdrawalsRaw[$key] ?? 0)), 3))->all();

        $salesForecastValues = $this->predictNextValues($salesSeries, 3, true);
        $purchaseForecastValues = $this->predictNextValues($purchaseSeries, 3, true);
        $cashflowForecastValues = $this->predictNextValues($cashflowSeries, 3, false);

        $forecastMonths = collect(range(1, 3))
            ->map(fn (int $offset) => company_today()->copy()->startOfMonth()->addMonths($offset)->format('M Y'))
            ->all();

        $currentMonthSales = (float) ($salesSeries[count($salesSeries) - 1] ?? 0);
        $currentMonthPurchases = (float) ($purchaseSeries[count($purchaseSeries) - 1] ?? 0);
        $currentMonthCashflow = (float) ($cashflowSeries[count($cashflowSeries) - 1] ?? 0);

        $activeClients = Client::query()->where('company_id', $companyId)->count();

        $last90InvoicesTotal = (float) Invoice::query()
            ->where('company_id', $companyId)
            ->whereBetween('date', [company_today()->copy()->subDays(90)->toDateString(), company_today()->toDateString()])
            ->sum('total');

        $last90Collected = (float) Invoice::query()
            ->where('company_id', $companyId)
            ->whereBetween('date', [company_today()->copy()->subDays(90)->toDateString(), company_today()->toDateString()])
            ->sum('amount_paid');

        $collectionRate = $last90InvoicesTotal > 0 ? round(($last90Collected / $last90InvoicesTotal) * 100, 1) : 0;

        return [
            'kpis' => [
                'current_month_sales' => round($currentMonthSales, 3),
                'current_month_purchases' => round($currentMonthPurchases, 3),
                'current_month_cashflow' => round($currentMonthCashflow, 3),
                'active_clients' => $activeClients,
                'collection_rate' => $collectionRate,
            ],
            'salesChart' => ['labels' => $labels, 'values' => $salesSeries],
            'purchaseChart' => ['labels' => $labels, 'values' => $purchaseSeries],
            'cashflowChart' => ['labels' => $labels, 'values' => $cashflowSeries],
            'salesPredictions' => collect($salesForecastValues)->map(fn (float $value, int $i) => [
                'label' => $forecastMonths[$i],
                'value' => round($value, 3),
            ])->all(),
            'purchasePredictions' => collect($purchaseForecastValues)->map(fn (float $value, int $i) => [
                'label' => $forecastMonths[$i],
                'value' => round($value, 3),
            ])->all(),
            'cashflowPredictions' => collect($cashflowForecastValues)->map(fn (float $value, int $i) => [
                'label' => $forecastMonths[$i],
                'value' => round($value, 3),
            ])->all(),
            'aiInsights' => $this->aiInsights,
        ];
    }

    /**
     * @param  array<int,float|int>  $series
     * @return array<int,float>
     */
    private function predictNextValues(array $series, int $months, bool $nonNegative): array
    {
        $n = count($series);

        if ($n === 0) {
            return array_fill(0, $months, 0.0);
        }

        if ($n === 1) {
            $value = (float) $series[0];

            return array_fill(0, $months, $nonNegative ? max(0.0, $value) : $value);
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumXX = 0.0;

        foreach ($series as $index => $value) {
            $x = (float) ($index + 1);
            $y = (float) $value;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);
        $slope = $denominator !== 0.0 ? (($n * $sumXY) - ($sumX * $sumY)) / $denominator : 0.0;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        $predictions = [];

        for ($i = 1; $i <= $months; $i++) {
            $x = (float) ($n + $i);
            $value = ($slope * $x) + $intercept;
            $predictions[] = $nonNegative ? max(0.0, $value) : $value;
        }

        return $predictions;
    }
}
