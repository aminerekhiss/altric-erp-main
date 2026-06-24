<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartCrmLead extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'smart_crm_leads';

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'email',
        'phone',
        'source',
        'status',
        'expected_value',
        'churn_risk',
        'activity_score',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expected_value' => 'decimal:3',
        'churn_risk' => 'decimal:2',
        'activity_score' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
