<?php

namespace App\Models\Accounting;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceComptabiliteTable extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'finance_comptabilite_tables';

    protected $fillable = [
        'company_id',
        'payment_method',
        'initial_balance',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:3',
    ];
}
