<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BusinessCompany extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'business_companies';

    protected $fillable = [
        'company_id',
        'user_id',
        'logo',
        'name',
        'email_primary',
        'email_secondary',
        'website',
        'phone_primary',
        'phone_secondary',
        'phone_tertiary',
        'created_by',
        'updated_by',
    ];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'business_company_employee')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
