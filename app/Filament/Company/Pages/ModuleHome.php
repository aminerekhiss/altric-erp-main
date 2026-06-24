<?php

namespace App\Filament\Company\Pages;

use App\Filament\Company\Clusters\Settings\Pages\CompanyDefault;
use App\Filament\Company\Pages\Accounting\AccountChart;
use App\Filament\Company\Pages\Finance;
use App\Filament\Company\Pages\Reports;
use App\Filament\Company\Pages\Service\ConnectedAccount;
use App\Filament\Company\Pages\Service\LiveCurrency;
use App\Filament\Company\Resources\Banking\AccountResource;
use App\Filament\Company\Resources\Inventory\ProductResource;
use App\Filament\Company\Resources\Inventory\TicketResource;
use App\Filament\Company\Resources\Purchases\BillResource;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Filament\Company\Resources\Sales\EmployeeAttendanceResource;
use App\Filament\Company\Resources\Sales\EmployeeResource;
use App\Filament\Company\Resources\Sales\InvoiceStegResource;
use Filament\Pages\Page;

class ModuleHome extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Home Modules';

    protected static ?int $navigationSort = -999;

    protected static ?string $title = 'Home Modules';

    protected static string $view = 'filament.company.pages.module-home';

    protected static ?string $slug = 'home';

    public function getViewData(): array
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        $isOwner = (bool) ($user && $company && $user->ownsCompany($company));

        $servicesUrl = null;
        if (ConnectedAccount::canAccess()) {
            $servicesUrl = ConnectedAccount::getUrl();
        } elseif (LiveCurrency::canAccess()) {
            $servicesUrl = LiveCurrency::getUrl();
        }

        $modules = [
            [
                'label' => translate('Sales'),
                'description' => translate('Clients, invoicing, and sales operations.'),
                'icon' => 'heroicon-o-shopping-bag',
                'color' => 'from-emerald-500 to-teal-500',
                'url' => ClientResource::getUrl('index'),
                'visible' => ClientResource::canViewAny(),
            ],
            [
                'label' => translate('Invoice STEG'),
                'description' => translate('STEG-format invoicing and print workflow.'),
                'icon' => 'heroicon-o-document-text',
                'color' => 'from-sky-500 to-blue-600',
                'url' => InvoiceStegResource::getUrl('index'),
                'visible' => InvoiceStegResource::canViewAny(),
            ],
            [
                'label' => translate('Companies'),
                'description' => translate('Employees and company entities.'),
                'icon' => 'heroicon-o-briefcase',
                'color' => 'from-violet-500 to-fuchsia-500',
                'url' => EmployeeResource::getUrl('index'),
                'visible' => EmployeeResource::canViewAny(),
            ],
            [
                'label' => translate('Ressources Humaines'),
                'description' => translate('Attendance, projects, and HR tracking.'),
                'icon' => 'heroicon-o-user-group',
                'color' => 'from-orange-500 to-amber-500',
                'url' => EmployeeAttendanceResource::getUrl('index'),
                'visible' => EmployeeAttendanceResource::canViewAny(),
            ],
            [
                'label' => translate('Product & Stock'),
                'description' => translate('Inventory catalog and stock management.'),
                'icon' => 'heroicon-o-package',
                'color' => 'from-indigo-500 to-blue-500',
                'url' => ProductResource::getUrl('index'),
                'visible' => ProductResource::canViewAny(),
            ],
            [
                'label' => translate('Tickets'),
                'description' => translate('Ticket processing and archive operations.'),
                'icon' => 'heroicon-o-inbox',
                'color' => 'from-rose-500 to-pink-500',
                'url' => TicketResource::getUrl('index'),
                'visible' => TicketResource::canViewAny(),
            ],
            [
                'label' => translate('Purchases'),
                'description' => translate('Bills and supplier purchasing flows.'),
                'icon' => 'heroicon-o-truck',
                'color' => 'from-lime-500 to-green-500',
                'url' => BillResource::getUrl('index'),
                'visible' => BillResource::canViewAny(),
            ],
            [
                'label' => translate('Accounting'),
                'description' => translate('Account chart and accounting entries.'),
                'icon' => 'heroicon-o-calculator',
                'color' => 'from-cyan-500 to-teal-600',
                'url' => AccountChart::getUrl(),
                'visible' => AccountChart::canAccess(),
            ],
            [
                'label' => translate('Finance'),
                'description' => translate('Tunisian fiscal dashboard and legal due tracking.'),
                'icon' => 'heroicon-o-chart-pie',
                'color' => 'from-emerald-500 to-cyan-600',
                'url' => Finance::getUrl(),
                'visible' => Finance::canAccess(),
            ],
            [
                'label' => translate('Banking'),
                'description' => translate('Bank accounts and financial connectivity.'),
                'icon' => 'heroicon-o-wallet',
                'color' => 'from-slate-500 to-gray-600',
                'url' => AccountResource::getUrl('index'),
                'visible' => AccountResource::canViewAny(),
            ],
            [
                'label' => translate('Services'),
                'description' => translate('Connected accounts and live currency tools.'),
                'icon' => 'heroicon-o-puzzle-piece',
                'color' => 'from-yellow-500 to-orange-500',
                'url' => $servicesUrl,
                'visible' => filled($servicesUrl),
            ],
            [
                'label' => translate('Reports'),
                'description' => translate('Financial and performance reporting.'),
                'icon' => 'heroicon-o-bars-3',
                'color' => 'from-purple-500 to-indigo-500',
                'url' => Reports::getUrl(),
                'visible' => Reports::canAccess(),
            ],
            [
                'label' => translate('Settings'),
                'description' => translate('Company configuration and default settings.'),
                'icon' => 'heroicon-o-cog-6-tooth',
                'color' => 'from-gray-500 to-zinc-600',
                'url' => CompanyDefault::getUrl(),
                'visible' => $isOwner,
            ],
        ];

        $visibleModules = collect($modules)
            ->filter(fn (array $module) => (bool) ($module['visible'] ?? false))
            ->values()
            ->all();

        return [
            'modules' => $visibleModules,
        ];
    }
}
