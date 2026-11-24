<?php

declare(strict_types=1);

namespace Modules\ERP\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Core\Overrides\Pivot;
use Modules\ERP\Enums\ERPTables;
use Modules\ERP\Models\PartnerPool;
use Override;

/**
 * @property int $id
 * @property int $partner_pool_id
 * @property int $user_id
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 */
final class PartnerPoolHasUser extends Pivot
{
    #[Override]
    public $incrementing = true;

    #[Override]
    public $timestamps = true;

    #[Override]
    protected $table = ERPTables::PartnerPoolMembers->value;

    #[Override]
    protected $fillable = ['partner_pool_id', 'user_id'];

    public function partner_pool(): BelongsTo
    {
        return $this->belongsTo(PartnerPool::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
