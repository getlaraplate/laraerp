<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Overrides\Model;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Database\Factories\WarehouseFactory;
use Modules\ERP\Enums\ERPTables;
use Override;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $code
 * @property int|null $site_id
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property bool|null $is_deleted Whether the entity is deleted
 */
final class Warehouse extends Model
{
    use BelongsToCompany;

    /**
     * @var string
     */
    #[Override]
    protected $table = ERPTables::Warehouses->value;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'site_id',
    ];

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<StockLevel, $this>
     */
    public function stock_levels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * @return Factory<self>
     */
    #[Override]
    protected static function newFactory(): Factory
    {
        return WarehouseFactory::new();
    }
}
