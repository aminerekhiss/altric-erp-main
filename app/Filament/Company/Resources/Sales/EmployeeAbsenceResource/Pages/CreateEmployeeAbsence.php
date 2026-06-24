<?php

namespace App\Filament\Company\Resources\Sales\EmployeeAbsenceResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\EmployeeAbsenceResource;
use App\Models\Common\Employee;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateEmployeeAbsence extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = EmployeeAbsenceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['employee_id'] = $this->sanitizeEmployeeId($data['employee_id'] ?? null);
        $data['days'] = $this->resolveDays($data['start_date'] ?? null, $data['end_date'] ?? null, $data['days'] ?? null);

        return parent::handleRecordCreation($data);
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

    protected function resolveDays(?string $startDate, ?string $endDate, mixed $days): int
    {
        if ($days) {
            return max(1, (int) $days);
        }

        if (! $startDate || ! $endDate) {
            throw ValidationException::withMessages([
                'data.days' => 'Start date and end date are required to calculate leave days.',
            ]);
        }

        $start = \Illuminate\Support\Carbon::parse($startDate);
        $end = \Illuminate\Support\Carbon::parse($endDate);

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'data.end_date' => 'End date must be greater than or equal to start date.',
            ]);
        }

        return $start->diffInDays($end) + 1;
    }
}
