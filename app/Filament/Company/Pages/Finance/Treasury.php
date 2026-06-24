<?php

namespace App\Filament\Company\Pages\Finance;

use App\Filament\Company\Pages\Finance;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use Filament\Pages\Page;

class Treasury extends Page
{
    protected static ?string $title = 'Treasury';

    protected static ?string $navigationLabel = 'Treasury';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'finance/treasury';

    protected static string $view = 'filament.company.pages.finance.treasury';

    public static function canAccess(): bool
    {
        return Finance::canAccess();
    }

    public function getViewData(): array
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return [
                'bankAccountsCount' => 0,
                'depositsMonth' => 0,
                'withdrawalsMonth' => 0,
                'recentTransactions' => collect(),
            ];
        }

        $start = company_today()->startOfMonth();
        $end = company_today()->endOfMonth();

        $bankAccountsCount = BankAccount::query()
            ->where('company_id', $companyId)
            ->count();

        $depositsMonth = (float) Transaction::query()
            ->where('company_id', $companyId)
            ->where('type', 'deposit')
            ->whereBetween('posted_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->sum('amount');

        $withdrawalsMonth = (float) Transaction::query()
            ->where('company_id', $companyId)
            ->where('type', 'withdrawal')
            ->whereBetween('posted_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->sum('amount');

        $recentTransactions = Transaction::query()
            ->where('company_id', $companyId)
            ->latest('posted_at')
            ->limit(12)
            ->get();

        return [
            'bankAccountsCount' => $bankAccountsCount,
            'depositsMonth' => $depositsMonth,
            'withdrawalsMonth' => $withdrawalsMonth,
            'recentTransactions' => $recentTransactions,
        ];
    }
}
