<?php

namespace App\Models\Service;

use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRecord extends Model
{
    use CompanyOwned;
    use HasFactory;

    protected $table = 'workflow_records';

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'module',
        'goal',
        'constraints',
        'workflow_title',
        'workflow_summary',
        'workflow_steps',
        'grok_raw_response',
    ];

    protected $casts = [
        'workflow_steps' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
