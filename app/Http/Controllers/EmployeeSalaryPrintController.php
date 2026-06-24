<?php

namespace App\Http\Controllers;

use App\Models\Common\EmployeeSalary;
use Illuminate\Support\Facades\Auth;

class EmployeeSalaryPrintController extends Controller
{
    public function show(EmployeeSalary $employeeSalary)
    {
        abort_unless(
            $employeeSalary->company_id === Auth::user()?->current_company_id,
            403,
            'Unauthorized salary access.'
        );

        return view('print-employee-salary', [
            'salary' => $employeeSalary->loadMissing(['employee', 'company']),
        ]);
    }
}
