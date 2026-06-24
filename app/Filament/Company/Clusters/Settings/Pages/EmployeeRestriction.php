<?php

namespace App\Filament\Company\Clusters\Settings\Pages;

use App\Filament\Company\Clusters\Settings;
use App\Models\Common\Employee;
use App\Support\EmployeeModuleAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class EmployeeRestriction extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Employee Restrictions';

    protected static ?string $title = 'Employee Module Restrictions';

    protected static string $view = 'filament.company.pages.employee-restriction';

    protected static ?string $cluster = Settings::class;

    public ?array $data = [];

    public ?Employee $employee = null;

    public function mount(): void
    {
        $this->data = [];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('employee_id')
                    ->label(translate('Select Employee'))
                    ->options(
                        Employee::query()
                            ->where('company_id', auth()->user()->currentCompany->id)
                            ->pluck('full_name', 'id')
                            ->toArray()
                    )
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadEmployeeAccess())
                    ->columnSpanFull(),
                $this->getEmployeeModuleAccessSection(),
            ])
            ->statePath('data');
    }

    protected function getEmployeeModuleAccessSection(): Component
    {
        return Section::make(translate('Module Access'))
            ->description(translate('Select which modules this employee can access'))
            ->schema([
                Grid::make(2)
                    ->schema([
                        Toggle::make('employee_module_access.business_companies')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_BUSINESS_COMPANIES]),
                        Toggle::make('employee_module_access.employees')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_EMPLOYEES]),
                        Toggle::make('employee_module_access.employee_absences')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_EMPLOYEE_ABSENCES]),
                        Toggle::make('employee_module_access.cars')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_CARS]),
                        Toggle::make('employee_module_access.employee_attendances')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_EMPLOYEE_ATTENDANCES]),
                        Toggle::make('employee_module_access.employee_week_offs')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_EMPLOYEE_WEEK_OFFS]),
                        Toggle::make('employee_module_access.employee_salaries')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_EMPLOYEE_SALARIES]),
                        Toggle::make('employee_module_access.messages')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_MESSAGES]),
                        Toggle::make('employee_module_access.products')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_PRODUCTS]),
                        Toggle::make('employee_module_access.stocks')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_STOCKS]),
                        Toggle::make('employee_module_access.tickets')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_TICKETS]),
                        Toggle::make('employee_module_access.invoice_archives')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_INVOICE_ARCHIVES]),
                        Toggle::make('employee_module_access.stock_movements')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_STOCK_MOVEMENTS]),
                        Toggle::make('employee_module_access.projects')
                            ->label(EmployeeModuleAccess::labels()[EmployeeModuleAccess::MODULE_PROJECTS]),
                    ]),
            ]);
    }

    protected function loadEmployeeAccess(): void
    {
        $employeeId = $this->data['employee_id'] ?? null;

        if (!$employeeId) {
            $this->data = ['employee_id' => null];
            return;
        }

        $this->employee = Employee::find($employeeId);

        if (!$this->employee) {
            return;
        }

        $access = EmployeeModuleAccess::normalize($this->employee->employee_module_access);

        $this->data = [
            'employee_id' => $employeeId,
            'employee_module_access' => $access,
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(translate('Save Restrictions'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public function save(): void
    {
        try {
            $employeeId = $this->data['employee_id'] ?? null;

            if (!$employeeId) {
                Notification::make()
                    ->warning()
                    ->title(translate('No employee selected'))
                    ->body(translate('Please select an employee first.'))
                    ->send();
                return;
            }

            $employee = Employee::find($employeeId);

            if (!$employee) {
                Notification::make()
                    ->danger()
                    ->title(translate('Employee not found'))
                    ->send();
                return;
            }

            $access = $this->data['employee_module_access'] ?? [];
            $employee->update([
                'employee_module_access' => EmployeeModuleAccess::normalize($access),
            ]);

            Notification::make()
                ->success()
                ->title(translate('Success'))
                ->body(translate('Employee module restrictions updated successfully.'))
                ->send();
        } catch (\Exception $exception) {
            Notification::make()
                ->danger()
                ->title(translate('Error'))
                ->body($exception->getMessage())
                ->send();

            throw new Halt();
        }
    }
}
