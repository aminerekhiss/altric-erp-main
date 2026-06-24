<?php

namespace App\Filament\Company\Resources\Sales\EmployeeSalaryResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\EmployeeSalaryResource;
use App\Models\Common\Employee;
use App\Services\EmployeeSalaryService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateEmployeeSalary extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = EmployeeSalaryResource::class;

    protected function handleRecordCreation(array $data): Model
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

        return parent::handleRecordCreation($data);
    }
}
