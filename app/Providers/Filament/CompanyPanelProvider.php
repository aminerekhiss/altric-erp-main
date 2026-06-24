<?php

namespace App\Providers\Filament;

use App\Actions\FilamentCompanies\AddCompanyEmployee;
use App\Actions\FilamentCompanies\CreateConnectedAccount;
use App\Actions\FilamentCompanies\CreateNewUser;
use App\Actions\FilamentCompanies\CreateUserFromProvider;
use App\Actions\FilamentCompanies\DeleteCompany;
use App\Actions\FilamentCompanies\DeleteUser;
use App\Actions\FilamentCompanies\HandleInvalidState;
use App\Actions\FilamentCompanies\InviteCompanyEmployee;
use App\Actions\FilamentCompanies\RemoveCompanyEmployee;
use App\Actions\FilamentCompanies\ResolveSocialiteUser;
use App\Actions\FilamentCompanies\SetUserPassword;
use App\Actions\FilamentCompanies\UpdateCompanyName;
use App\Actions\FilamentCompanies\UpdateConnectedAccount;
use App\Actions\FilamentCompanies\UpdateUserPassword;
use App\Actions\FilamentCompanies\UpdateUserProfileInformation;
use App\Filament\Company\Clusters\Settings;
use App\Filament\Company\Pages\Accounting\AccountChart;
use App\Filament\Company\Pages\CreateCompany;
use App\Filament\Company\Pages\Finance;
use App\Filament\Company\Pages\Finance\AnalysesPredictions;
use App\Filament\Company\Pages\Finance\Echance;
use App\Filament\Company\Pages\Finance\TableauComptabilite;
use App\Filament\Company\Pages\ManageCompany;
use App\Filament\Company\Pages\Reports;
use App\Filament\Company\Pages\SmartCrm;
use App\Filament\Company\Pages\Service\ConnectedAccount;
use App\Filament\Company\Pages\Service\LiveCurrency;
use App\Filament\Company\Pages\Service\Workflow;
use App\Filament\Company\Resources\Accounting\BudgetResource;
use App\Filament\Company\Resources\Accounting\TransactionResource;
use App\Filament\Company\Resources\Banking\AccountResource;
use App\Filament\Company\Resources\Common\OfferingResource;
use App\Filament\Company\Resources\Inventory\InvoiceArchiveResource;
use App\Filament\Company\Resources\Inventory\ProductResource;
use App\Filament\Company\Resources\Inventory\StockMovementResource;
use App\Filament\Company\Resources\Inventory\StockResource;
use App\Filament\Company\Resources\Inventory\TicketResource;
use App\Filament\Company\Resources\Purchases\BillResource;
use App\Filament\Company\Resources\Purchases\VendorResource;
use App\Filament\Company\Resources\Sales\BusinessCompanyResource;
use App\Filament\Company\Resources\Sales\AccountMessageResource;
use App\Filament\Company\Resources\Sales\CarResource;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Filament\Company\Resources\Sales\EmployeeAbsenceResource;
use App\Filament\Company\Resources\Sales\EmployeeAttendanceResource;
use App\Filament\Company\Resources\Sales\EmployeeResource;
use App\Filament\Company\Resources\Sales\EmployeeSalaryResource;
use App\Filament\Company\Resources\Sales\EmployeeWeekOffResource;
use App\Filament\Company\Resources\Sales\EstimateResource;
use App\Filament\Company\Resources\Sales\InvoiceResource;
use App\Filament\Company\Resources\Sales\ParametrableInvoiceResource;
use App\Filament\Company\Resources\Sales\ProjectResource;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource;
use App\Filament\Company\Resources\Sales\UserResource;
use App\Filament\Components\PanelShiftDropdown;
use App\Filament\Pages\Auth\Login;
use App\Filament\User\Clusters\Account;
use App\Http\Middleware\ConfigureCurrentCompany;
use App\Livewire\UpdatePassword;
use App\Livewire\UpdateProfileInformation;
use App\Models\Company;
use App\Services\CompanySettingsService;
use App\Support\FilamentComponentConfigurator;
use Exception;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Components\TextEntry;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Widgets;
use Filament\View\PanelsRenderHook;
use Filament\Support\Facades\FilamentView;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Wallo\FilamentCompanies\Actions\GenerateRedirectForProvider;
use Wallo\FilamentCompanies\Enums\Feature;
use Wallo\FilamentCompanies\Enums\Provider;
use Wallo\FilamentCompanies\FilamentCompanies;
use Wallo\FilamentCompanies\Pages\Auth\Register;

class CompanyPanelProvider extends PanelProvider
{
    /**
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        $isDemoEnvironment = is_demo_environment();

        return $panel
            ->default()
            ->id('company')
            ->path('company')
            ->login(Login::class)
            ->when(! $isDemoEnvironment, function (Panel $panel) {
                return $panel
                    ->registration(Register::class)
                    ->passwordReset();
            })
            ->tenantMenu(false)
            ->plugins([
                FilamentCompanies::make()
                    ->userPanel('user')
                    ->switchCurrentCompany()
                    ->updateProfileInformation(component: UpdateProfileInformation::class)
                    ->updatePasswords(component: UpdatePassword::class)
                    ->setPasswords()
                    ->connectedAccounts()
                    ->manageBrowserSessions()
                    ->accountDeletion()
                    ->profilePhotos()
                    ->api()
                    ->companies(invitations: true)
                    ->autoAcceptInvitations()
                    ->termsAndPrivacyPolicy()
                    ->notifications()
                    ->modals()
                    ->socialite(
                        condition: ! $isDemoEnvironment,
                        providers: [Provider::Github],
                        features: [Feature::RememberSession, Feature::ProviderAvatars],
                    ),
                PanelShiftDropdown::make()
                    ->logoutItem()
                    ->companySettings()
                    ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                        return $builder
                            ->items(Account::getNavigationItems());
                    }),
            ])
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                $user = auth()->user();
                $canAccessSettings = $user && $user->currentCompany && $user->ownsCompany($user->currentCompany);

                return $builder
                    ->items([
                        ...Reports::getNavigationItems(),
                        ...($canAccessSettings ? Settings::getNavigationItems() : []),
                    ])
                    ->groups([
                        NavigationGroup::make('Finance')
                            ->label(translate('Finance'))
                            ->icon('heroicon-o-chart-pie')
                            ->items([
                                ...Finance::getNavigationItems(),
                                ...AnalysesPredictions::getNavigationItems(),
                                ...Echance::getNavigationItems(),
                                ...TableauComptabilite::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Facturation')
                            ->label(translate('Facturation'))
                            ->icon('heroicon-o-document-text')
                            ->items([
                                ...ClientResource::getNavigationItems(),
                                ...EstimateResource::getNavigationItems(),
                                ...InvoiceResource::getNavigationItems(),
                                ...ParametrableInvoiceResource::getNavigationItems(),
                                ...RecurringInvoiceResource::getNavigationItems(),
                                ...InvoiceArchiveResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Smart CRM')
                            ->label(translate('Smart CRM'))
                            ->icon('heroicon-o-chart-bar-square')
                            ->items([
                                NavigationItem::make(translate('Smart CRM'))
                                    ->icon('heroicon-o-chart-bar-square')
                                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.company.pages.smart-crm'))
                                    ->url(SmartCrm::getUrl())
                                    ->visible(SmartCrm::canAccess()),
                            ]),
                        NavigationGroup::make('Achat')
                            ->label(translate('Achat'))
                            ->icon('heroicon-o-shopping-cart')
                            ->items([
                                ...VendorResource::getNavigationItems(),
                                ...BillResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Entreprise')
                            ->label(translate('Entreprise'))
                            ->icon('heroicon-o-building-office-2')
                            ->items([
                                ...BusinessCompanyResource::getNavigationItems(),
                                ...UserResource::getNavigationItems(),
                                ...OfferingResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Ressources Humaines')
                            ->label(translate('Ressources Humaines'))
                            ->icon('heroicon-o-users')
                            ->items([
                                ...EmployeeResource::getNavigationItems(),
                                ...EmployeeAttendanceResource::getNavigationItems(),
                                ...EmployeeAbsenceResource::getNavigationItems(),
                                ...EmployeeWeekOffResource::getNavigationItems(),
                                ...EmployeeSalaryResource::getNavigationItems(),
                                ...CarResource::getNavigationItems(),
                                ...ProjectResource::getNavigationItems(),
                                ...AccountMessageResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Inventaire')
                            ->label(translate('Inventaire'))
                            ->icon('heroicon-o-cube')
                            ->items([
                                ...ProductResource::getNavigationItems(),
                                ...StockResource::getNavigationItems(),
                                ...StockMovementResource::getNavigationItems(),
                                ...TicketResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Comptabilité')
                            ->label(translate('Comptabilité'))
                            ->icon('heroicon-o-clipboard-document-list')
                            ->extraSidebarAttributes(['class' => 'es-sidebar-group'])
                            ->items([
                                ...AccountChart::getNavigationItems(),
                                ...TransactionResource::getNavigationItems(),
                                ...BudgetResource::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Banque')
                            ->label(translate('Banque'))
                            ->icon('heroicon-o-building-library')
                            ->items(AccountResource::getNavigationItems()),
                        NavigationGroup::make('Services')
                            ->label(translate('Services'))
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->items([
                                ...ConnectedAccount::getNavigationItems(),
                                ...LiveCurrency::getNavigationItems(),
                                ...Workflow::getNavigationItems(),
                            ]),
                    ]);
            })
            ->globalSearch(false)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications(isLazy: false)
            ->viteTheme('resources/css/filament/company/theme.css')
            ->favicon(asset('icon.ico'))
            ->brandLogo(static fn () => view('components.icons.logo'))
            ->tenant(Company::class)
            ->tenantProfile(ManageCompany::class)
            ->tenantRegistration(CreateCompany::class)
            ->discoverResources(in: app_path('Filament/Company/Resources'), for: 'App\\Filament\\Company\\Resources')
            ->discoverPages(in: app_path('Filament/Company/Pages'), for: 'App\\Filament\\Company\\Pages')
            ->discoverClusters(in: app_path('Filament/Company/Clusters'), for: 'App\\Filament\\Company\\Clusters')
            ->pages([
                // Pages\Dashboard::class,
            ])
            ->authGuard('web')
            ->discoverWidgets(in: app_path('Filament/Company/Widgets'), for: 'App\\Filament\\Company\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->tenantMiddleware([
                ConfigureCurrentCompany::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();
        $this->configureDefaults();

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            static function (): string {
                if (Filament::getCurrentPanel()?->getId() !== 'company') {
                    return '';
                }

                return (string) view('components.floating-chat-button');
            }
        );

        FilamentCompanies::createUsersUsing(CreateNewUser::class);
        FilamentCompanies::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        FilamentCompanies::updateUserPasswordsUsing(UpdateUserPassword::class);

        FilamentCompanies::createCompaniesUsing(CreateCompany::class);
        FilamentCompanies::updateCompanyNamesUsing(UpdateCompanyName::class);
        FilamentCompanies::addCompanyEmployeesUsing(AddCompanyEmployee::class);
        FilamentCompanies::inviteCompanyEmployeesUsing(InviteCompanyEmployee::class);
        FilamentCompanies::removeCompanyEmployeesUsing(RemoveCompanyEmployee::class);
        FilamentCompanies::deleteCompaniesUsing(DeleteCompany::class);
        FilamentCompanies::deleteUsersUsing(DeleteUser::class);

        FilamentCompanies::resolvesSocialiteUsersUsing(ResolveSocialiteUser::class);
        FilamentCompanies::createUsersFromProviderUsing(CreateUserFromProvider::class);
        FilamentCompanies::createConnectedAccountsUsing(CreateConnectedAccount::class);
        FilamentCompanies::updateConnectedAccountsUsing(UpdateConnectedAccount::class);
        FilamentCompanies::setUserPasswordsUsing(SetUserPassword::class);
        FilamentCompanies::handlesInvalidStateUsing(HandleInvalidState::class);
        FilamentCompanies::generatesProvidersRedirectsUsing(GenerateRedirectForProvider::class);
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        FilamentCompanies::defaultApiTokenPermissions(['read']);

        FilamentCompanies::role('admin', 'Administrator', [
            'create',
            'read',
            'update',
            'delete',
        ])->description('Administrator users can perform any action.');

        FilamentCompanies::role('editor', 'Editor', [
            'read',
            'create',
            'update',
        ])->description('Editor users have the ability to read, create, and update.');

        FilamentCompanies::role('company', 'Company Account', [
            'read',
            'create',
            'update',
            'delete',
        ])->description('Company accounts can operate business modules, while company ownership actions remain restricted by policy.');

        FilamentCompanies::role('employee', 'Employee Account', [
            'read',
            'create',
            'update',
        ])->description('Employee accounts can work on day-to-day operations without destructive delete privileges.');
    }

    /**
     * Configure the default settings for Filament.
     */
    protected function configureDefaults(): void
    {
        $this->configureSelect();

        Forms\Components\FileUpload::configureUsing(function (Forms\Components\FileUpload $component): void {
            $component
                ->hidden(is_demo_environment());
        });

        Actions\CreateAction::configureUsing(static fn (Actions\CreateAction $action) => FilamentComponentConfigurator::configureActionModals($action));
        Actions\EditAction::configureUsing(static fn (Actions\EditAction $action) => FilamentComponentConfigurator::configureActionModals($action));
        Actions\DeleteAction::configureUsing(static fn (Actions\DeleteAction $action) => FilamentComponentConfigurator::configureDeleteAction($action));
        Tables\Actions\EditAction::configureUsing(static fn (Tables\Actions\EditAction $action) => FilamentComponentConfigurator::configureActionModals($action));
        Tables\Actions\CreateAction::configureUsing(static fn (Tables\Actions\CreateAction $action) => FilamentComponentConfigurator::configureActionModals($action));
        Tables\Actions\DeleteAction::configureUsing(static fn (Tables\Actions\DeleteAction $action) => FilamentComponentConfigurator::configureDeleteAction($action));
        Tables\Actions\DeleteBulkAction::configureUsing(static fn (Tables\Actions\DeleteBulkAction $action) => FilamentComponentConfigurator::configureDeleteAction($action));

        Tables\Table::configureUsing(static function (Tables\Table $table): void {
            $table::$defaultDateDisplayFormat = CompanySettingsService::getDefaultDateFormat();
            $table::$defaultTimeDisplayFormat = CompanySettingsService::getDefaultTimeFormat();
            $table::$defaultDateTimeDisplayFormat = CompanySettingsService::getDefaultDateTimeFormat();

            $table
                ->paginationPageOptions([5, 10, 25, 50, 100])
                ->filtersFormWidth(MaxWidth::Small)
                ->filtersTriggerAction(
                    fn (Tables\Actions\Action $action) => $action
                        ->button()
                        ->label('Filters')
                        ->slideOver()
                );
        });

        Tables\Columns\TextColumn::configureUsing(function (Tables\Columns\TextColumn $column): void {
            $column->placeholder('–');
        });

        TextEntry::configureUsing(function (TextEntry $component): void {
            $component->placeholder('–');
        });

        Tables\Actions\ExportAction::configureUsing(function (Tables\Actions\ExportAction $action) {
            $action
                ->color('primary')
                ->slideOver();
        });
    }

    /**
     * Configure the default settings for the Select component.
     */
    protected function configureSelect(): void
    {
        Select::configureUsing(function (Select $select): void {
            $select
                ->native(false)
                ->selectablePlaceholder(fn (Select $component) => ! $component->isRequired());
        });
    }
}
