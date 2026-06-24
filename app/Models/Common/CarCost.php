<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarCost extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'car_costs';

    protected $fillable = [
        'company_id',
        'car_id',
        'cost_type',
        'cost_date',
        'amount',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cost_date' => 'date',
        'amount' => 'decimal:3',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }
}
