<?php

namespace App\Filament\Company\Pages\Finance;

use App\Filament\Company\Pages\Finance;
use App\Models\Accounting\Invoice;
use Filament\Pages\Page;

class AccountsReceivable extends Page
{
    protected static ?string $title = 'Accounts Receivable';

    protected static ?string $navigationLabel = 'Accounts Receivable';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-circle';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'finance/accounts-receivable';

    protected static string $view = 'filament.company.pages.finance.accounts-receivable';

    public static function canAccess(): bool
    {
        return Finance::canAccess();
    }

    public function getViewData(): array
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return [
                'totalOpen' => 0,
                'totalOverdue' => 0,
                'recentInvoices' => collect(),
            ];
        }

        $totalOpen = (float) Invoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->sum('total');

        $totalOverdue = (float) Invoice::query()
            ->where('company_id', $companyId)
            ->where('status', 'overdue')
            ->sum('total');

        $recentInvoices = Invoice::query()
            ->where('company_id', $companyId)
            ->latest('date')
            ->limit(12)
            ->get();

        return [
            'totalOpen' => $totalOpen,
            'totalOverdue' => $totalOverdue,
            'recentInvoices' => $recentInvoices,
        ];
    }
}
