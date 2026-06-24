<?php

namespace App\Services;

use App\Models\Common\Employee;
use App\Models\Common\EmployeeAbsence;
use App\Models\Common\EmployeeAttendance;
use App\Models\Common\EmployeeWeekOff;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EmployeeSalaryService
{
    /**
     * @return array{paid_days: float, absent_days: float, week_off_days: float, net_salary: int}
     */
    public function calculate(Employee $employee, string $salaryMonth, int $baseSalary, int $bonus = 0, int $deduction = 0): array
    {
        $monthStart = Carbon::parse($salaryMonth)->startOfMonth();
        $monthEnd = Carbon::parse($salaryMonth)->endOfMonth();

        $attendanceByDate = EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn (EmployeeAttendance $attendance) => Carbon::parse($attendance->attendance_date)->toDateString());

        $weekOffRules = EmployeeWeekOff::query()
            ->where('employee_id', $employee->id)
            ->where(function ($query) use ($monthEnd) {
                $query->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $monthEnd->toDateString());
            })
            ->where(function ($query) use ($monthStart) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $monthStart->toDateString());
            })
            ->get();

        $approvedAbsenceDates = EmployeeAbsence::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('start_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('end_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhere(function ($nested) use ($monthStart, $monthEnd) {
                        $nested->whereDate('start_date', '<=', $monthStart->toDateString())
                            ->whereDate('end_date', '>=', $monthEnd->toDateString());
                    });
            })
            ->get();

        $approvedLeaveMap = [];
        foreach ($approvedAbsenceDates as $absence) {
            $absenceStart = Carbon::parse($absence->start_date)->max($monthStart);
            $absenceEnd = Carbon::parse($absence->end_date)->min($monthEnd);

            foreach (CarbonPeriod::create($absenceStart, $absenceEnd) as $leaveDate) {
                $approvedLeaveMap[$leaveDate->toDateString()] = $absence->type;
            }
        }

        $paidDays = 0.0;
        $absentDays = 0.0;
        $weekOffDays = 0.0;

        foreach (CarbonPeriod::create($monthStart, $monthEnd) as $date) {
            $dateKey = $date->toDateString();
            $isWeekOff = false;
            $isWeekOffPaid = false;

            foreach ($weekOffRules as $weekOffRule) {
                if ($weekOffRule->weekday !== $date->dayOfWeek) {
                    continue;
                }

                $startsBefore = ! $weekOffRule->effective_from || Carbon::parse($weekOffRule->effective_from)->lte($date);
                $endsAfter = ! $weekOffRule->effective_to || Carbon::parse($weekOffRule->effective_to)->gte($date);

                if ($startsBefore && $endsAfter) {
                    $isWeekOff = true;
                    $isWeekOffPaid = (bool) $weekOffRule->is_paid;
                    break;
                }
            }

            if ($isWeekOff) {
                $weekOffDays += 1;
                if ($isWeekOffPaid) {
                    $paidDays += 1;
                }

                continue;
            }

            $attendance = $attendanceByDate->get($dateKey);
            $approvedLeaveType = $approvedLeaveMap[$dateKey] ?? null;

            if ($attendance) {
                if (in_array($attendance->status, [EmployeeAttendance::STATUS_PRESENT, EmployeeAttendance::STATUS_LATE, EmployeeAttendance::STATUS_REMOTE], true)) {
                    $paidDays += 1;
                    continue;
                }

                if ($attendance->status === EmployeeAttendance::STATUS_HALF_DAY) {
                    $paidDays += 0.5;
                    $absentDays += 0.5;
                    continue;
                }

                $absentDays += 1;
                continue;
            }

            if ($approvedLeaveType === EmployeeAbsence::TYPE_CONGE) {
                $paidDays += 1;
                continue;
            }

            $absentDays += 1;
        }

        $daysInMonth = max(1, $monthStart->daysInMonth);
        $netSalary = (int) round((($baseSalary / $daysInMonth) * $paidDays) + $bonus - $deduction);

        return [
            'paid_days' => $paidDays,
            'absent_days' => $absentDays,
            'week_off_days' => $weekOffDays,
            'net_salary' => max(0, $netSalary),
        ];
    }
}
