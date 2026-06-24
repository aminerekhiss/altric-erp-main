<?php

namespace App\Filament\Company\Clusters\Settings\Pages;

use App\Events\CompanyDefaultUpdated;
use App\Filament\Company\Clusters\Settings;
use App\Models\Banking\BankAccount;
use App\Models\Setting\CompanyDefault as CompanyDefaultModel;
use App\Support\EmployeeModuleAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

use function Filament\authorize;

/**
 * @property Form $form
 */
class CompanyDefault extends Page
{
    use InteractsWithFormActions;

    protected static ?string $title = 'Default';

    protected static string $view = 'filament.company.pages.setting.company-default';

    protected static ?string $cluster = Settings::class;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    #[Locked]
    public ?CompanyDefaultModel $record = null;

    public function getTitle(): string | Htmlable
    {
        return translate(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return translate(static::$title);
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::ScreenTwoExtraLarge;
    }

    public function mount(): void
    {
        $this->record = CompanyDefaultModel::firstOrNew([
            'company_id' => auth()->user()->current_company_id,
        ]);

        abort_unless(static::canView($this->record), 404);

        $this->fillForm();
    }

    public function fillForm(): void
    {
        $data = $this->record->attributesToArray();

        $this->form->fill($data);
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $this->handleRecordUpdate($this->record, $data);

        } catch (Halt $exception) {
            return;
        }

        $this->getSavedNotification()->send();
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getGeneralSection(),
                $this->getEmployeeModuleAccessSection(),
            ])
            ->model($this->record)
            ->statePath('data')
            ->operation('edit');
    }

    protected function getGeneralSection(): Component
    {
        return Section::make(translate('General'))
            ->schema([
                Select::make('bank_account_id')
                    ->localizeLabel()
                    ->relationship(
                        'bankAccount',
                        'name',
                        modifyQueryUsing: static fn (Builder $query): Builder => $query->where('company_id', auth()->user()?->current_company_id)
                    )
                    ->getOptionLabelFromRecordUsing(function (BankAccount $record) {
                        $name = $record->account->name;
                        $currency = $this->renderBadgeOptionLabel($record->account->currency_code);

                        return "{$name} ⁓ {$currency}";
                    })
                    ->allowHtml()
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->searchable()
                    ->preload(),
                Placeholder::make('currency_code')
                    ->label(translate('Currency'))
                    ->hintIcon('heroicon-o-question-mark-circle', translate('You cannot change this after your company has been created. You can still use other currencies for transactions.'))
                    ->content(static fn (CompanyDefaultModel $record) => "{$record->currency->code} {$record->currency->symbol} - {$record->currency->name}"),
            ])->columns();
    }

    protected function getEmployeeModuleAccessSection(): Component
    {
        return Section::make(translate('Employee Module Access'))
            ->description(translate('Choose which modules employee accounts can open and use.'))
            ->schema([
                Toggle::make('employee_module_access.business_companies')
                    ->label(translate('Companies'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_BUSINESS_COMPANIES]),
                Toggle::make('employee_module_access.employees')
                    ->label(translate('Employees'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_EMPLOYEES]),
                Toggle::make('employee_module_access.employee_absences')
                    ->label(translate('Absences / Conges'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_EMPLOYEE_ABSENCES]),
                Toggle::make('employee_module_access.cars')
                    ->label(translate('Cars'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_CARS]),
                Toggle::make('employee_module_access.employee_attendances')
                    ->label(translate('Attendance'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_EMPLOYEE_ATTENDANCES]),
                Toggle::make('employee_module_access.employee_week_offs')
                    ->label(translate('Weeks Off'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_EMPLOYEE_WEEK_OFFS]),
                Toggle::make('employee_module_access.employee_salaries')
                    ->label(translate('Salaries'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_EMPLOYEE_SALARIES]),
                Toggle::make('employee_module_access.messages')
                    ->label(translate('Messages'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_MESSAGES]),
                Toggle::make('employee_module_access.products')
                    ->label(translate('Products'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_PRODUCTS]),
                Toggle::make('employee_module_access.stocks')
                    ->label(translate('Stock'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_STOCKS]),
                Toggle::make('employee_module_access.tickets')
                    ->label(translate('Tickets'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_TICKETS]),
                Toggle::make('employee_module_access.invoice_archives')
                    ->label(translate('Invoice Archives'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_INVOICE_ARCHIVES]),
                Toggle::make('employee_module_access.stock_movements')
                    ->label(translate('Stock Movements'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_STOCK_MOVEMENTS]),
                Toggle::make('employee_module_access.projects')
                    ->label(translate('Projects'))
                    ->default(EmployeeModuleAccess::defaults()[EmployeeModuleAccess::MODULE_PROJECTS]),
            ])
            ->columns(2);
    }

    public function renderBadgeOptionLabel(string $label): string
    {
        return Blade::render('filament::components.badge', [
            'color' => 'primary',
            'size' => 'sm',
            'slot' => $label,
        ]);
    }

    protected function handleRecordUpdate(CompanyDefaultModel $record, array $data): CompanyDefaultModel
    {
        $companyId = auth()->user()?->current_company_id;

        if ($companyId) {
            $data['company_id'] = $companyId;
        }

        if (filled($data['bank_account_id'] ?? null)) {
            $hasValidBankAccount = BankAccount::query()
                ->when($companyId, fn (Builder $query): Builder => $query->where('company_id', $companyId))
                ->whereKey((int) $data['bank_account_id'])
                ->exists();

            if (! $hasValidBankAccount) {
                throw ValidationException::withMessages([
                    'data.bank_account_id' => 'Selected bank account is invalid for your company.',
                ]);
            }
        }

        CompanyDefaultUpdated::dispatch($record, $data);

        $record->update($data);

        return $record;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public static function canView(Model $record): bool
    {
        try {
            return authorize('update', $record)->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }
}
