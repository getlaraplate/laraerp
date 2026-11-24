<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Overrides\Model;
use Modules\ERP\Casts\DeliveryNoteDirection;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Database\Factories\DeliveryNoteFactory;
use Modules\ERP\Enums\ERPTables;
use Modules\ERP\Observers\DeliveryNoteObserver;
use Override;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $sales_order_id
 * @property DeliveryNoteDirection $direction
 * @property string|null $reference
 * @property \Carbon\CarbonImmutable|null $delivered_at
 * @property \Carbon\CarbonImmutable|null $posted_at
 * @property \Carbon\CarbonImmutable|null $inventory_posted_at
 * @property string|null $notes
 * @property int|null $cogs_journal_entry_id
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
#[ObservedBy([DeliveryNoteObserver::class])]
final class DeliveryNote extends Model
{
    use BelongsToCompany;

    /**
     * @var string
     */
    #[Override]
    protected $table = ERPTables::DeliveryNotes->value;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'company_id',
        'sales_order_id',
        'direction',
        'reference',
        'delivered_at',
        'posted_at',
        'inventory_posted_at',
        'cogs_journal_entry_id',
        'notes',
    ];

    /**
     * @return BelongsTo<SalesOrder, $this>
     */
    public function sales_order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * @return HasMany<DeliveryNoteLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryNoteLine::class);
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function cogs_journal_entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'cogs_journal_entry_id');
    }

    /**
     * @return Factory<self>
     */
    #[Override]
    protected static function newFactory(): Factory
    {
        return DeliveryNoteFactory::new();
    }

    protected function casts(): array
    {
        return [
            'direction' => DeliveryNoteDirection::class,
            'delivered_at' => 'datetime',
            'posted_at' => 'datetime',
            'inventory_posted_at' => 'datetime',
        ];
    }
}
