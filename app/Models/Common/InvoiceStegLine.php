<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceStegLine extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'invoice_steg_lines';

    protected $fillable = [
        'company_id',
        'invoice_steg_id',
        'code',
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
        return $this->belongsTo(InvoiceSteg::class, 'invoice_steg_id');
    }
}
