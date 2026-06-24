<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParametrableInvoice extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'parametrable_invoices';

    protected $fillable = [
        'company_id',
        'invoice_number',
        'client_name',
        'object',
        'date',
        'currency_code',
        'is_structure',
        'structure_name',
        'print_logo',
        'print_header',
        'print_footer',
        'total_ht',
        'adjustments_total',
        'net_ht',
        'amount_in_words',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_structure' => 'boolean',
        'total_ht' => 'decimal:3',
        'adjustments_total' => 'decimal:3',
        'net_ht' => 'decimal:3',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(ParametrableInvoiceLine::class, 'parametrable_invoice_id')
            ->orderBy('line_order');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(ParametrableInvoiceAdjustment::class, 'parametrable_invoice_id')
            ->orderBy('sort_order');
    }
}
