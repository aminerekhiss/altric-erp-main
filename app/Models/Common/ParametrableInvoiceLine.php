<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametrableInvoiceLine extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'parametrable_invoice_lines';

    protected $fillable = [
        'company_id',
        'parametrable_invoice_id',
        'product_id',
        'designation',
        'unit',
        'quantity',
        'puht',
        'ptht',
        'line_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'puht' => 'decimal:3',
        'ptht' => 'decimal:3',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ParametrableInvoice::class, 'parametrable_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
