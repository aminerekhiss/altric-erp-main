<?php

namespace App\Filament\Company\Resources\Sales\EmployeeSalaryResource\Pages;

use App\Filament\Company\Resources\Sales\EmployeeSalaryResource;
use App\Models\Common\Employee;
use App\Models\Common\EmployeeSalary;
use App\Services\EmployeeSalaryService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListEmployeeSalaries extends ListRecords
{
    protected static string $resource = EmployeeSalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateMonthlySalaries')
                ->label('Generate Monthly Salaries')
                ->icon('heroicon-m-calculator')
                ->color('warning')
                ->form([
                    Forms\Components\DatePicker::make('salary_month')
                        ->label('Salary month')
                        ->default(company_today()->startOfMonth()->toDateString())
                        ->required(),
                    Forms\Components\TextInput::make('default_base_salary')
                        ->label('Default base salary')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required()
                        ->helperText('Used when an employee has no previous salary record.'),
                    Forms\Components\Select::make('status')
                        ->options(EmployeeSalary::getStatusOptions())
                        ->default(EmployeeSalary::STATUS_DRAFT)
                        ->required()
                        ->native(false),
                    Forms\Components\Toggle::make('overwrite_existing')
                        ->label('Overwrite existing salaries for this month')
                        ->default(false),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $salaryMonth = Carbon::parse($data['salary_month'])->startOfMonth()->toDateString();
                    $defaultBaseSalary = (int) ($data['default_base_salary'] ?? 0);
                    $status = (string) ($data['status'] ?? EmployeeSalary::STATUS_DRAFT);
                    $overwriteExisting = (bool) ($data['overwrite_existing'] ?? false);

                    $created = 0;
                    $updated = 0;
                    $skipped = 0;

                    /** @var \Illuminate\Database\Eloquent\Collection<int, Employee> $employees */
                    $employees = Employee::query()->get();

                    foreach ($employees as $employee) {
                        if (! $employee instanceof Employee) {
                            continue;
                        }

                        $existing = EmployeeSalary::query()
                            ->where('employee_id', $employee->id)
                            ->whereDate('salary_month', $salaryMonth)
                            ->first();

                        if ($existing && ! $overwriteExisting) {
                            $skipped++;

                            continue;
                        }

                        $lastSalary = EmployeeSalary::query()
                            ->where('employee_id', $employee->id)
                            ->latest('salary_month')
                            ->first();

                        $baseSalary = (int) ($lastSalary?->base_salary ?? $defaultBaseSalary);
                        $bonus = 0;
                        $deduction = 0;

                        $result = app(EmployeeSalaryService::class)->calculate(
                            employee: $employee,
                            salaryMonth: $salaryMonth,
                            baseSalary: $baseSalary,
                            bonus: $bonus,
                            deduction: $deduction,
                        );

                        $payload = array_merge($result, [
                            'employee_id' => $employee->id,
                            'salary_month' => $salaryMonth,
                            'base_salary' => $baseSalary,
                            'bonus' => $bonus,
                            'deduction' => $deduction,
                            'status' => $status,
                            'paid_at' => $status === EmployeeSalary::STATUS_PAID ? now() : null,
                        ]);

                        if ($existing) {
                            $existing->update($payload);
                            $updated++;
                        } else {
                            EmployeeSalary::query()->create($payload);
                            $created++;
                        }
                    }

                    Notification::make()
                        ->title('Monthly salaries generated')
                        ->success()
                        ->body("Created: {$created} | Updated: {$updated} | Skipped: {$skipped}")
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }
}
