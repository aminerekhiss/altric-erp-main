<?php

namespace App\Filament\Company\Pages\Finance;

use App\Filament\Company\Pages\Finance;
use App\Models\Accounting\Bill;
use Filament\Pages\Page;

class AccountsPayable extends Page
{
    protected static ?string $title = 'Accounts Payable';

    protected static ?string $navigationLabel = 'Accounts Payable';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'finance/accounts-payable';

    protected static string $view = 'filament.company.pages.finance.accounts-payable';

    public static function canAccess(): bool
    {
        return Finance::canAccess();
    }

    public function getViewData(): array
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return [
                'totalOutstanding' => 0,
                'totalDueSoon' => 0,
                'recentBills' => collect(),
            ];
        }

        $today = company_today();

        $totalOutstanding = (float) Bill::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->sum('total');

        $totalDueSoon = (float) Bill::query()
            ->where('company_id', $companyId)
            ->whereDate('due_date', '<=', $today->copy()->addDays(15)->toDateString())
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->sum('total');

        $recentBills = Bill::query()
            ->where('company_id', $companyId)
            ->latest('date')
            ->limit(12)
            ->get();

        return [
            'totalOutstanding' => $totalOutstanding,
            'totalDueSoon' => $totalDueSoon,
            'recentBills' => $recentBills,
        ];
    }
}
