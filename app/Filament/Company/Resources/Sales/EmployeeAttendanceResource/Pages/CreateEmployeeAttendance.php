<?php

namespace App\Filament\Company\Resources\Sales\EmployeeAttendanceResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\EmployeeAttendanceResource;
use App\Models\Common\Employee;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateEmployeeAttendance extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = EmployeeAttendanceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['employee_id'] = $this->sanitizeEmployeeId($data['employee_id'] ?? null);
        $data['worked_minutes'] = $this->resolveWorkedMinutes(
            $data['check_in'] ?? null,
            $data['check_out'] ?? null,
            $data['worked_minutes'] ?? null,
        );

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

    protected function resolveWorkedMinutes(?string $checkIn, ?string $checkOut, mixed $minutes): ?int
    {
        if ($minutes !== null && $minutes !== '') {
            return max(0, (int) $minutes);
        }

        if (! $checkIn || ! $checkOut) {
            return null;
        }

        $start = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'data.check_out' => 'Check out must be greater than or equal to check in.',
            ]);
        }

        return $start->diffInMinutes($end);
    }
}
