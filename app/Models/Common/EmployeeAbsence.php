<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAbsence extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    public const TYPE_ABSENCE = 'absence';

    public const TYPE_CONGE = 'conge';

    protected $table = 'employee_absences';

    protected $fillable = [
        'company_id',
        'employee_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'days',
        'reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'integer',
    ];

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_ABSENCE => 'Absence',
            self::TYPE_CONGE => 'Conge',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
