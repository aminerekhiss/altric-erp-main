<?php

namespace App\Models\Accounting;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceNote extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'finance_notes';

    protected $fillable = [
        'company_id',
        'category',
        'title',
        'note',
        'note_date',
        'amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'note_date' => 'date',
        'amount' => 'decimal:3',
    ];
}
