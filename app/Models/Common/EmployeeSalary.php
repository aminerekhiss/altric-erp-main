<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    protected $table = 'employee_salaries';

    protected $fillable = [
        'company_id',
        'employee_id',
        'salary_month',
        'base_salary',
        'bonus',
        'deduction',
        'net_salary',
        'paid_days',
        'absent_days',
        'week_off_days',
        'status',
        'paid_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'salary_month' => 'date',
        'paid_at' => 'datetime',
        'base_salary' => 'integer',
        'bonus' => 'integer',
        'deduction' => 'integer',
        'net_salary' => 'integer',
        'paid_days' => 'float',
        'absent_days' => 'float',
        'week_off_days' => 'float',
    ];

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PAID => 'Paid',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
