<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    public const TYPE_ENTRANCE = 'entrance';

    public const TYPE_EXIT = 'exit';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'tickets';

    protected $fillable = [
        'company_id',
        'product_id',
        'new_product_name',
        'client_id',
        'invoice_folder',
        'invoice_description',
        'type',
        'status',
        'name',
        'provider',
        'date',
        'quantity',
        'logo',
        'invoice_file',
        'invoice_date',
        'invoice_from',
        'invoice_amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'invoice_date' => 'date',
        'invoice_amount' => 'decimal:3',
        'quantity' => 'integer',
    ];

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_ENTRANCE => 'Entrance Ticket',
            self::TYPE_EXIT => 'Exit Ticket',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_VALIDATED => 'Validated',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'ticket_id');
    }
}
