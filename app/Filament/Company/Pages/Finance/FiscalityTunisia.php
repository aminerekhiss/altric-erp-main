<?php

namespace App\Filament\Company\Pages\Finance;

use App\Filament\Company\Pages\Finance;
use App\Models\Common\InvoiceSteg;
use Filament\Pages\Page;

class FiscalityTunisia extends Page
{
    protected static ?string $title = 'Fiscality Tunisia';

    protected static ?string $navigationLabel = 'Fiscality Tunisia';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'finance/fiscality-tunisia';

    protected static string $view = 'filament.company.pages.finance.fiscality-tunisia';

    public static function canAccess(): bool
    {
        return Finance::canAccess();
    }

    public function getViewData(): array
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return [
                'totalHt' => 0,
                'tva19' => 0,
                'rg5' => 0,
                'retenue1' => 0,
                'tva25' => 0,
                'netAPayer' => 0,
            ];
        }

        $from = company_today()->startOfMonth();
        $to = company_today()->endOfMonth();

        $totals = InvoiceSteg::query()
            ->where('company_id', $companyId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(total_ht),0) as total_ht')
            ->selectRaw('COALESCE(SUM(tva_19),0) as tva_19')
            ->selectRaw('COALESCE(SUM(rg_5),0) as rg_5')
            ->selectRaw('COALESCE(SUM(retenue_source_1),0) as retenue_source_1')
            ->selectRaw('COALESCE(SUM(tva_25),0) as tva_25')
            ->selectRaw('COALESCE(SUM(net_a_payer),0) as net_a_payer')
            ->first();

        return [
            'totalHt' => (float) ($totals->total_ht ?? 0),
            'tva19' => (float) ($totals->tva_19 ?? 0),
            'rg5' => (float) ($totals->rg_5 ?? 0),
            'retenue1' => (float) ($totals->retenue_source_1 ?? 0),
            'tva25' => (float) ($totals->tva_25 ?? 0),
            'netAPayer' => (float) ($totals->net_a_payer ?? 0),
        ];
    }
}
