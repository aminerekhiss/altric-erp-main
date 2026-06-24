<?php

namespace App\Filament\Company\Widgets;

use App\Filament\Widgets\EnhancedStatsOverviewWidget;
use App\Models\Common\EmployeeAttendance;
use App\Models\Common\EmployeeSalary;
use App\Support\EmployeeModuleAccess;
use Illuminate\Support\Facades\Auth;

class HrOverviewWidget extends EnhancedStatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = Auth::user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        return EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_EMPLOYEE_ATTENDANCES, $user, $company)
            || EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_EMPLOYEE_SALARIES, $user, $company);
    }

    protected function getStats(): array
    {
        $today = company_today()->toDateString();
        $monthStart = company_today()->startOfMonth()->toDateString();
        $monthEnd = company_today()->endOfMonth()->toDateString();

        $presentToday = EmployeeAttendance::query()
            ->whereDate('attendance_date', $today)
            ->whereIn('status', [
                EmployeeAttendance::STATUS_PRESENT,
                EmployeeAttendance::STATUS_LATE,
                EmployeeAttendance::STATUS_HALF_DAY,
                EmployeeAttendance::STATUS_REMOTE,
            ])
            ->count();

        $absentToday = EmployeeAttendance::query()
            ->whereDate('attendance_date', $today)
            ->where('status', EmployeeAttendance::STATUS_ABSENT)
            ->count();

        $payrollDue = EmployeeSalary::query()
            ->whereBetween('salary_month', [$monthStart, $monthEnd])
            ->where('status', '!=', EmployeeSalary::STATUS_PAID)
            ->sum('net_salary');

        return [
            EnhancedStatsOverviewWidget\EnhancedStat::make('Present Today', (string) $presentToday)
                ->description('Employees marked present/late/remote/half-day'),
            EnhancedStatsOverviewWidget\EnhancedStat::make('Absent Today', (string) $absentToday)
                ->description('Employees marked absent today'),
            EnhancedStatsOverviewWidget\EnhancedStat::make('Payroll Due This Month', number_format((float) $payrollDue, 2))
                ->description('Unpaid salaries for current month'),
        ];
    }
}
