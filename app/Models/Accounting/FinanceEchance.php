<?php

namespace App\Models\Accounting;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceEchance extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    public const ENTRY_ENTREE = 'entree';

    public const ENTRY_SORTIE = 'sortie';

    protected $table = 'finance_echances';

    protected $fillable = [
        'company_id',
        'source_type',
        'source_id',
        'reference',
        'entry_type',
        'amount',
        'echance_date',
        'supplier',
        'is_paid',
        'payment_method',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'echance_date' => 'date',
        'is_paid' => 'boolean',
    ];

    public static function entryTypeOptions(): array
    {
        return [
            self::ENTRY_ENTREE => 'Entree',
            self::ENTRY_SORTIE => 'Sortie',
        ];
    }
}
