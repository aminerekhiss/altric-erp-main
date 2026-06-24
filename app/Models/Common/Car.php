<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'cars';

    protected $fillable = [
        'company_id',
        'car_number',
        'mission',
        'mission_date',
        'assurance_date',
        'assurance_amount',
        'vignette_date',
        'vignette_amount',
        'visite_date',
        'visite_amount',
        'additional_cost_date',
        'additional_cost_amount',
        'additional_cost_note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mission_date' => 'date',
        'assurance_date' => 'date',
        'vignette_date' => 'date',
        'visite_date' => 'date',
        'additional_cost_date' => 'date',
        'assurance_amount' => 'decimal:3',
        'vignette_amount' => 'decimal:3',
        'visite_amount' => 'decimal:3',
        'additional_cost_amount' => 'decimal:3',
    ];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'car_employee')
            ->withTimestamps();
    }

    public function carCosts(): HasMany
    {
        return $this->hasMany(CarCost::class, 'car_id')
            ->orderByDesc('cost_date');
    }
}
