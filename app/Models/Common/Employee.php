<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'employees';

    protected $fillable = [
        'company_id',
        'user_id',
        'full_name',
        'email',
        'phone',
        'rib',
        'employee_module_access',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'employee_module_access' => 'array',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(BusinessCompany::class, 'business_company_employee')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(EmployeeAbsence::class, 'employee_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class, 'employee_id');
    }

    public function weekOffs(): HasMany
    {
        return $this->hasMany(EmployeeWeekOff::class, 'employee_id');
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class, 'employee_id');
    }

    public function cars(): BelongsToMany
    {
        return $this->belongsToMany(Car::class, 'car_employee')
            ->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'employee_project')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }
}
