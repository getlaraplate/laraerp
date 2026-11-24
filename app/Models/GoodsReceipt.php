<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Overrides\Model;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Enums\ERPTables;
use Modules\ERP\Observers\GoodsReceiptObserver;
use Override;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $purchase_order_id
 * @property string|null $reference
 * @property \Carbon\CarbonImmutable|null $received_at
 * @property \Carbon\CarbonImmutable|null $posted_at
 * @property \Carbon\CarbonImmutable|null $inventory_posted_at
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property bool|null $is_deleted Whether the entity is deleted
 * @property string|null $locked_at The date and time when the entity was locked
 * @property int|null $locked_user_id The user who locked the entity
 * @property string|null $locked_until The moment the lock expires; null means it never does
 * @property string|null $valid_from
 * @property string|null $valid_to
 */
#[ObservedBy([GoodsReceiptObserver::class])]
final class GoodsReceipt extends Model
{
    use BelongsToCompany;

    /**
     * @var string
     */
    #[Override]
    protected $table = ERPTables::GoodsReceipts->value;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'reference',
        'received_at',
        'posted_at',
        'inventory_posted_at',
        'notes',
    ];

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return HasMany<GoodsReceiptLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'posted_at' => 'datetime',
            'inventory_posted_at' => 'datetime',
        ];
    }
}
