<?php

namespace App\Filament\Company\Pages\Finance;

use App\Filament\Company\Pages\Finance;
use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Transaction;
use Filament\Pages\Page;

class GeneralLedger extends Page
{
    protected static ?string $title = 'General Ledger';

    protected static ?string $navigationLabel = 'General Ledger';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'finance/general-ledger';

    protected static string $view = 'filament.company.pages.finance.general-ledger';

    public static function canAccess(): bool
    {
        return Finance::canAccess();
    }

    public function getViewData(): array
    {
        $companyId = auth()->user()?->current_company_id;

        $accountsCount = $companyId ? Account::query()->where('company_id', $companyId)->count() : 0;
        $entriesCount = $companyId ? JournalEntry::query()->where('company_id', $companyId)->count() : 0;

        $recentTransactions = $companyId
            ? Transaction::query()
                ->where('company_id', $companyId)
                ->latest('posted_at')
                ->limit(12)
                ->get()
            : collect();

        return [
            'accountsCount' => $accountsCount,
            'entriesCount' => $entriesCount,
            'recentTransactions' => $recentTransactions,
        ];
    }
}
