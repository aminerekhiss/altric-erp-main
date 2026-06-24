<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWeekOff extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'employee_week_offs';

    protected $fillable = [
        'company_id',
        'employee_id',
        'weekday',
        'is_paid',
        'effective_from',
        'effective_to',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'is_paid' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public static function getWeekdayOptions(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
