<?php

namespace App\Filament\Company\Pages;

use App\Models\Accounting\FinanceNote;
use App\Models\Common\Car;
use App\Models\Common\CarCost;
use App\Models\Common\InvoiceSteg;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class Finance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Finance';

    protected static ?string $title = 'Finance';

    protected static ?int $navigationSort = 999;

    protected static ?string $slug = 'finance';

    protected static string $view = 'filament.company.pages.finance';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addFinanceNote')
                ->label(translate('Add Finance Note'))
                ->icon('heroicon-o-pencil-square')
                ->form([
                    Forms\Components\Select::make('category')
                        ->label(translate('Category'))
                        ->options([
                            'tax' => translate('Tax'),
                            'vehicle' => translate('Vehicle'),
                            'cashflow' => translate('Cashflow'),
                            'payment' => translate('Payment'),
                            'other' => translate('Other'),
                        ])
                        ->default('other')
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->label(translate('Title'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('note_date')
                        ->label(translate('Date'))
                        ->default(company_today()->toDateString()),
                    Forms\Components\TextInput::make('amount')
                        ->label(translate('Amount'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('TND'),
                    Forms\Components\Textarea::make('note')
                        ->label(translate('Note'))
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    FinanceNote::query()->create([
                        'category' => $data['category'] ?? 'other',
                        'title' => $data['title'],
                        'note' => $data['note'],
                        'note_date' => $data['note_date'] ?? company_today()->toDateString(),
                        'amount' => $data['amount'] ?? null,
                    ]);
                }),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        return $user->ownsCompany($company) || $user->hasCompanyPermission($company, 'read');
    }

    public function getViewData(): array
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return [
                'kpis' => [],
                'upcomingLegalDeadlines' => [],
                'recentCarCosts' => [],
                'recentFinanceNotes' => [],
            ];
        }

        $today = company_today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $invoiceStegMonthly = InvoiceSteg::query()
            ->where('company_id', $companyId)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('COALESCE(SUM(total_ht),0) as total_ht')
            ->selectRaw('COALESCE(SUM(tva_19),0) as tva_19')
            ->selectRaw('COALESCE(SUM(rg_5),0) as rg_5')
            ->selectRaw('COALESCE(SUM(retenue_source_1),0) as retenue_source_1')
            ->selectRaw('COALESCE(SUM(tva_25),0) as tva_25')
            ->selectRaw('COALESCE(SUM(net_a_payer),0) as net_a_payer')
            ->first();

        $monthlyCarCosts = CarCost::query()
            ->where('company_id', $companyId)
            ->whereBetween('cost_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('COALESCE(SUM(amount),0) as amount')
            ->value('amount');

        $upcomingLegalDeadlines = collect();

        $cars = Car::query()
            ->where('company_id', $companyId)
            ->get();

        foreach ($cars as $car) {
            $deadlineMap = [
                'Assurance' => ['date' => $car->assurance_date, 'amount' => $car->assurance_amount],
                'Vignette' => ['date' => $car->vignette_date, 'amount' => $car->vignette_amount],
                'Visite Technique' => ['date' => $car->visite_date, 'amount' => $car->visite_amount],
            ];

            foreach ($deadlineMap as $label => $payload) {
                if (! $payload['date']) {
                    continue;
                }

                $date = Carbon::parse($payload['date']);

                if ($date->between($today->copy()->startOfDay(), $today->copy()->addDays(30)->endOfDay())) {
                    $upcomingLegalDeadlines->push([
                        'car_number' => $car->car_number,
                        'type' => $label,
                        'date' => $date,
                        'amount' => $payload['amount'],
                    ]);
                }
            }
        }

        $upcomingLegalDeadlines = $upcomingLegalDeadlines
            ->sortBy('date')
            ->values()
            ->all();

        $recentCarCosts = CarCost::query()
            ->where('company_id', $companyId)
            ->with('car:id,car_number')
            ->latest('cost_date')
            ->limit(10)
            ->get();

        $recentFinanceNotes = FinanceNote::query()
            ->where('company_id', $companyId)
            ->latest('note_date')
            ->latest('id')
            ->limit(12)
            ->get();

        $kpis = [
            'total_ht' => (float) ($invoiceStegMonthly->total_ht ?? 0),
            'tva_19' => (float) ($invoiceStegMonthly->tva_19 ?? 0),
            'rg_5' => (float) ($invoiceStegMonthly->rg_5 ?? 0),
            'retenue_source_1' => (float) ($invoiceStegMonthly->retenue_source_1 ?? 0),
            'tva_25' => (float) ($invoiceStegMonthly->tva_25 ?? 0),
            'net_a_payer' => (float) ($invoiceStegMonthly->net_a_payer ?? 0),
            'monthly_car_costs' => (float) ($monthlyCarCosts ?? 0),
        ];

        return [
            'kpis' => $kpis,
            'upcomingLegalDeadlines' => $upcomingLegalDeadlines,
            'recentCarCosts' => $recentCarCosts,
            'recentFinanceNotes' => $recentFinanceNotes,
        ];
    }
}
