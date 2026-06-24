<?php

namespace App\Filament\Company\Resources\Sales\EmployeeWeekOffResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\EmployeeWeekOffResource;
use App\Models\Common\Employee;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateEmployeeWeekOff extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = EmployeeWeekOffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['employee_id'] = $this->sanitizeEmployeeId($data['employee_id'] ?? null);

        return $data;
    }

    protected function sanitizeEmployeeId(mixed $employeeId): int
    {
        $companyId = Auth::user()?->current_company_id;

        $employee = Employee::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->find((int) $employeeId);

        if (! $employee) {
            throw ValidationException::withMessages([
                'data.employee_id' => 'Selected employee is invalid for your company.',
            ]);
        }

        return (int) $employee->id;
    }
}
