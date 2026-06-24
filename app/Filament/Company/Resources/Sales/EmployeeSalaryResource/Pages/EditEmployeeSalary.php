<?php

namespace App\Filament\Company\Resources\Sales\EmployeeSalaryResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\EmployeeSalaryResource;
use App\Models\Common\Employee;
use App\Models\Common\EmployeeSalary;
use App\Services\EmployeeSalaryService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditEmployeeSalary extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = EmployeeSalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('markAsPaid')
                ->label('Mark as paid')
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->visible(fn () => $this->record instanceof EmployeeSalary && $this->record->status !== EmployeeSalary::STATUS_PAID)
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var EmployeeSalary $salary */
                    $salary = $this->record;
                    $salary->update([
                        'status' => EmployeeSalary::STATUS_PAID,
                        'paid_at' => now(),
                    ]);
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $companyId = Auth::user()?->current_company_id;

        $employee = Employee::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->find($data['employee_id'] ?? null);

        if (! $employee) {
            throw ValidationException::withMessages([
                'data.employee_id' => 'Employee is required.',
            ]);
        }

        $salaryMonth = Carbon::parse($data['salary_month'])->startOfMonth()->toDateString();

        $result = app(EmployeeSalaryService::class)->calculate(
            employee: $employee,
            salaryMonth: $salaryMonth,
            baseSalary: (int) ($data['base_salary'] ?? 0),
            bonus: (int) ($data['bonus'] ?? 0),
            deduction: (int) ($data['deduction'] ?? 0),
        );

        $data['salary_month'] = $salaryMonth;
        $data = array_merge($data, $result);

        return parent::handleRecordUpdate($record, $data);
    }
}
