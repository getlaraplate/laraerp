<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\IsPartOfParent;
use Modules\Core\Overrides\Model;
use Modules\ERP\Casts\BillingMode;
use Modules\ERP\Enums\ERPTables;
use Override;

/**
 * @property int $id
 * @property int $quotation_id
 * @property int|null $price_list_item_id
 * @property string $name The name of the quotation item
 * @property BillingMode $billing_mode The billing mode of the quotation item
 * @property numeric-string $quantity The quantity of the quotation item
 * @property numeric-string|null $unit_price Unit price in quotation currency context; null if only descriptive line
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property bool|null $is_deleted Whether the entity is deleted
 */
final class QuotationItem extends Model implements IsPartOfParent
{
    /**
     * @var string
     */
    #[Override]
    protected $table = ERPTables::QuotationItems->value;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'quotation_id',
        'price_list_item_id',
        'name',
        'billing_mode',
        'quantity',
        'unit_price',
    ];

    /**
     * The relation to the record this one only exists inside, whose visibility it inherits.
     */
    #[Override]
    public function parentRelation(): string
    {
        return 'quotation';
    }

    /**
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * @return BelongsTo<PriceListItem, $this>
     */
    public function price_list_item(): BelongsTo
    {
        return $this->belongsTo(PriceListItem::class);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules['create'] = array_merge($rules['create'], [
            'quotation_id' => ['required', 'integer', 'exists:' . ERPTables::Quotations->value . ',id'],
            'price_list_item_id' => ['nullable', 'integer', 'exists:' . ERPTables::PriceListItems->value . ',id'],
            'name' => ['required', 'string', 'max:255'],
            'billing_mode' => ['required', 'string', BillingMode::validationRule()],
            'quantity' => ['required', 'numeric', 'min:0.0001', 'max:65535'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'quotation_id' => ['sometimes', 'integer', 'exists:' . ERPTables::Quotations->value . ',id'],
            'price_list_item_id' => ['nullable', 'integer', 'exists:' . ERPTables::PriceListItems->value . ',id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'billing_mode' => ['sometimes', 'string', BillingMode::validationRule()],
            'quantity' => ['sometimes', 'numeric', 'min:0.0001', 'max:65535'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        return $rules;
    }

    protected function casts(): array
    {
        return [
            'billing_mode' => BillingMode::class,
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
        ];
    }
}
