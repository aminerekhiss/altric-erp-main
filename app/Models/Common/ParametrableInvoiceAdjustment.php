<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametrableInvoiceAdjustment extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'parametrable_invoice_adjustments';

    protected $fillable = [
        'company_id',
        'parametrable_invoice_id',
        'label',
        'operation',
        'percentage',
        'amount',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'percentage' => 'decimal:3',
        'amount' => 'decimal:3',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ParametrableInvoice::class, 'parametrable_invoice_id');
    }
}
