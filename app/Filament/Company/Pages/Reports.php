<?php

namespace App\Filament\Company\Pages;

use App\Filament\Company\Pages\Reports\AccountBalances;
use App\Filament\Company\Pages\Reports\AccountsPayableAging;
use App\Filament\Company\Pages\Reports\AccountsReceivableAging;
use App\Filament\Company\Pages\Reports\AccountTransactions;
use App\Filament\Company\Pages\Reports\BalanceSheet;
use App\Filament\Company\Pages\Reports\CashFlowStatement;
use App\Filament\Company\Pages\Reports\ClientBalanceSummary;
use App\Filament\Company\Pages\Reports\ClientPaymentPerformance;
use App\Filament\Company\Pages\Reports\IncomeStatement;
use App\Filament\Company\Pages\Reports\TrialBalance;
use App\Filament\Company\Pages\Reports\VendorBalanceSummary;
use App\Filament\Company\Pages\Reports\VendorPaymentPerformance;
use App\Filament\Infolists\Components\ReportEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Infolist;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static string $view = 'filament.company.pages.reports';

    public function getTitle(): string
    {
        return translate(static::$navigationLabel);
    }

    public static function getNavigationLabel(): string
    {
        return translate(static::$navigationLabel);
    }

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs([
                    static::getRouteName(),
                    static::getRouteName() . '.*',
                ]))
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->url(static::getNavigationUrl()),
        ];
    }

    public function reportsInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([])
            ->schema([
                Section::make(translate('Financial Statements'))
                    ->aside()
                    ->description(translate('Key financial statements that provide an overview of your company’s financial health and performance.'))
                    ->extraAttributes(['class' => 'es-report-card'])
                    ->schema([
                        ReportEntry::make('income_statement')
                            ->hiddenLabel()
                            ->heading(translate('Income Statement'))
                            ->description(translate('Shows revenue, expenses, and net earnings over a period, indicating overall financial performance.'))
                            ->icon('heroicon-o-chart-bar')
                            ->iconColor(Color::Purple)
                            ->url(IncomeStatement::getUrl()),
                        ReportEntry::make('balance_sheet')
                            ->hiddenLabel()
                            ->heading(translate('Balance Sheet'))
                            ->description(translate('Displays your company’s assets, liabilities, and equity at a single point in time, showing overall financial health and stability.'))
                            ->icon('heroicon-o-clipboard-document-list')
                            ->iconColor(Color::Teal)
                            ->url(BalanceSheet::getUrl()),
                        ReportEntry::make('cash_flow_statement')
                            ->hiddenLabel()
                            ->heading(translate('Cash Flow Statement'))
                            ->description(translate('Tracks cash inflows and outflows, giving insight into liquidity and cash management over a period.'))
                            ->icon('heroicon-o-document-currency-dollar')
                            ->iconColor(Color::Cyan)
                            ->url(CashFlowStatement::getUrl()),
                    ]),
                Section::make(translate('Client Reports'))
                    ->aside()
                    ->description(translate('Reports that provide detailed information on your company’s client transactions and balances.'))
                    ->extraAttributes(['class' => 'es-report-card'])
                    ->schema([
                        ReportEntry::make('ar_aging')
                            ->hiddenLabel()
                            ->heading(translate('Accounts Receivable Aging'))
                            ->description(translate('Lists outstanding receivables by client, showing how long invoices have been unpaid.'))
                            ->icon('heroicon-o-calendar-date-range')
                            ->iconColor(Color::Indigo)
                            ->url(AccountsReceivableAging::getUrl()),
                        ReportEntry::make('client_balance_summary')
                            ->hiddenLabel()
                            ->heading(translate('Client Balance Summary'))
                            ->description(translate('Shows total invoiced amounts, payments received, and outstanding balances for each client, helping identify top clients and opportunities for growth.'))
                            ->icon('heroicon-o-receipt-percent')
                            ->iconColor(Color::Emerald)
                            ->url(ClientBalanceSummary::getUrl()),
                        ReportEntry::make('client_payment_performance')
                            ->hiddenLabel()
                            ->heading(translate('Client Payment Performance'))
                            ->description(translate('Analyzes payment behavior showing average days to pay, on-time payment rates, and late payment patterns for each client.'))
                            ->icon('heroicon-o-clock')
                            ->iconColor(Color::Fuchsia)
                            ->url(ClientPaymentPerformance::getUrl()),
                    ]),
                Section::make(translate('Vendor Reports'))
                    ->aside()
                    ->description(translate('Reports that provide detailed information on your company’s vendor transactions and balances.'))
                    ->extraAttributes(['class' => 'es-report-card'])
                    ->schema([
                        ReportEntry::make('ap_aging')
                            ->hiddenLabel()
                            ->heading(translate('Accounts Payable Aging'))
                            ->description(translate('Lists outstanding payables by vendor, showing how long invoices have been unpaid.'))
                            ->icon('heroicon-o-clock')
                            ->iconColor(Color::Rose)
                            ->url(AccountsPayableAging::getUrl()),
                        ReportEntry::make('vendor_balance_summary')
                            ->hiddenLabel()
                            ->heading(translate('Vendor Balance Summary'))
                            ->description(translate('Shows total billed amounts, payments made, and outstanding balances for each vendor, helping track payment obligations and vendor relationships.'))
                            ->icon('heroicon-o-banknotes')
                            ->iconColor(Color::Orange)
                            ->url(VendorBalanceSummary::getUrl()),
                        ReportEntry::make('vendor_payment_performance')
                            ->hiddenLabel()
                            ->heading(translate('Vendor Payment Performance'))
                            ->description(translate('Analyzes payment behavior showing average days to pay, on-time payment rates, and late payment patterns for each vendor.'))
                            ->icon('heroicon-o-clock')
                            ->iconColor(Color::Violet)
                            ->url(VendorPaymentPerformance::getUrl()),
                    ]),
                Section::make(translate('Detailed Reports'))
                    ->aside()
                    ->description(translate('Detailed reports that provide a comprehensive view of your company’s financial transactions and account balances.'))
                    ->extraAttributes(['class' => 'es-report-card'])
                    ->schema([
                        ReportEntry::make('account_balances')
                            ->hiddenLabel()
                            ->heading(translate('Account Balances'))
                            ->description(translate('Lists all accounts and their balances, including starting, debit, credit, net movement, and ending balances.'))
                            ->icon('heroicon-o-calculator')
                            ->iconColor(Color::Slate)
                            ->url(AccountBalances::getUrl()),
                        ReportEntry::make('trial_balance')
                            ->hiddenLabel()
                            ->heading(translate('Trial Balance'))
                            ->description(translate('Summarizes all account debits and credits on a specific date to verify the ledger is balanced.'))
                            ->icon('heroicon-o-scale')
                            ->iconColor(Color::Sky)
                            ->url(TrialBalance::getUrl()),
                        ReportEntry::make('account_transactions')
                            ->hiddenLabel()
                            ->heading(translate('Account Transactions'))
                            ->description(translate('A record of all transactions, essential for monitoring and reconciling financial activity in the ledger.'))
                            ->icon('heroicon-o-list-bullet')
                            ->iconColor(Color::Yellow)
                            ->url(AccountTransactions::getUrl()),
                    ]),
            ]);
    }
}
