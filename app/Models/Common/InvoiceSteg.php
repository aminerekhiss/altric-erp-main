<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceSteg extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'invoice_stegs';

    protected $fillable = [
        'company_id',
        'invoice_number',
        'invoice_city',
        'date',
        'client_name',
        'client_address',
        'object',
        'bon_de_commande',
        'currency_code',
        'total_ht',
        'tva_19',
        'rg_5',
        'total_ttc',
        'retenue_source_1',
        'tva_25',
        'net_a_payer',
        'amount_in_words',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_ht' => 'decimal:3',
        'tva_19' => 'decimal:3',
        'rg_5' => 'decimal:3',
        'total_ttc' => 'decimal:3',
        'retenue_source_1' => 'decimal:3',
        'tva_25' => 'decimal:3',
        'net_a_payer' => 'decimal:3',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceStegLine::class, 'invoice_steg_id')
            ->orderBy('line_order');
    }

    public static function getNextInvoiceNumber(): string
    {
        $year = company_today()->format('Y');

        $last = static::query()
            ->whereYear('date', (int) $year)
            ->get(['invoice_number'])
            ->map(function (self $invoice) {
                if (! is_string($invoice->invoice_number)) {
                    return 0;
                }

                if (preg_match('/(\d+)\s*\/\s*\d{4}$/', $invoice->invoice_number, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max();

        $next = ((int) $last) + 1;

        return $next . '/' . $year;
    }
}
