<?php

namespace App\Filament\Company\Resources\Sales\EmployeeResource\Pages;

use App\Filament\Company\Resources\Sales\EmployeeResource;
use App\Models\Common\Employee;
use App\Support\EmployeeModuleAccess;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assignModules')
                ->label('Assign modules')
                ->icon('heroicon-o-shield-check')
                ->visible(fn (): bool => EmployeeResource::canEditAny())
                ->form([
                    Forms\Components\Select::make('employee_ids')
                        ->label('Employees')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function (): array {
                            $companyId = auth()->user()?->current_company_id;

                            if (! $companyId) {
                                return [];
                            }

                            return Employee::query()
                                ->where('company_id', $companyId)
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id')
                                ->all();
                        })
                        ->required(),
                    Forms\Components\Select::make('module_keys')
                        ->label('Modules')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(EmployeeModuleAccess::labels())
                        ->required(),
                    Forms\Components\Select::make('access_state')
                        ->label('Access')
                        ->options([
                            'allow' => 'Allow selected modules',
                            'block' => 'Block selected modules',
                        ])
                        ->default('allow')
                        ->native(false)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $companyId = auth()->user()?->current_company_id;

                    if (! $companyId) {
                        throw ValidationException::withMessages([
                            'employee_ids' => 'No active company selected.',
                        ]);
                    }

                    $this->applyModuleAccessToEmployees(
                        employeeIds: array_map('intval', $data['employee_ids'] ?? []),
                        moduleKeys: array_values($data['module_keys'] ?? []),
                        accessState: (string) ($data['access_state'] ?? 'allow'),
                        companyId: (int) $companyId,
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('assignModules')
                ->label('Assign modules')
                ->icon('heroicon-o-shield-check')
                ->visible(fn (): bool => EmployeeResource::canEditAny())
                ->form([
                    Forms\Components\Select::make('module_keys')
                        ->label('Modules')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(EmployeeModuleAccess::labels())
                        ->required(),
                    Forms\Components\Select::make('access_state')
                        ->label('Access')
                        ->options([
                            'allow' => 'Allow selected modules',
                            'block' => 'Block selected modules',
                        ])
                        ->default('allow')
                        ->native(false)
                        ->required(),
                ])
                ->action(function (Collection $records, array $data): void {
                    $companyId = auth()->user()?->current_company_id;

                    if (! $companyId) {
                        throw ValidationException::withMessages([
                            'module_keys' => 'No active company selected.',
                        ]);
                    }

                    $this->applyModuleAccessToEmployees(
                        employeeIds: $records->pluck('id')->map(fn ($id): int => (int) $id)->all(),
                        moduleKeys: array_values($data['module_keys'] ?? []),
                        accessState: (string) ($data['access_state'] ?? 'allow'),
                        companyId: (int) $companyId,
                    );
                }),
        ];
    }

    /**
     * @param array<int, int> $employeeIds
     * @param array<int, string> $moduleKeys
     */
    protected function applyModuleAccessToEmployees(array $employeeIds, array $moduleKeys, string $accessState, int $companyId): void
    {
        $employeeIds = array_values(array_unique($employeeIds));
        $moduleKeys = array_values(array_unique($moduleKeys));
        $knownModules = array_keys(EmployeeModuleAccess::defaults());
        $moduleKeys = array_values(array_intersect($moduleKeys, $knownModules));

        if ($employeeIds === []) {
            throw ValidationException::withMessages([
                'employee_ids' => 'Please select at least one employee.',
            ]);
        }

        if ($moduleKeys === []) {
            throw ValidationException::withMessages([
                'module_keys' => 'Please select at least one module.',
            ]);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Employee> $employees */
        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $employeeIds)
            ->get();

        /** @var Employee $employee */
        foreach ($employees as $employee) {
            $access = EmployeeModuleAccess::normalize($employee->employee_module_access);

            foreach ($moduleKeys as $moduleKey) {
                $access[$moduleKey] = $accessState === 'allow';
            }

            $employee->employee_module_access = $access;
            $employee->save();
        }

        Notification::make()
            ->title('Module access updated for selected employees')
            ->success()
            ->send();
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }
}
