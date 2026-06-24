<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountMessage extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'account_messages';

    protected $fillable = [
        'company_id',
        'sender_user_id',
        'recipient_user_id',
        'subject',
        'message',
        'attachment_path',
        'read_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function isUnreadFor(int $userId): bool
    {
        return (int) $this->recipient_user_id === $userId && $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at !== null) {
            return;
        }

        $this->update([
            'read_at' => company_now(),
        ]);
    }
}
