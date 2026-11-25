<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Modules\Core\Contracts\ILockableModel;
use Modules\Core\Contracts\IValidatableModel;
use Modules\Core\Locking\Traits\HasLocks;
use Modules\Core\Models\Concerns\HasValidity;
use Modules\Core\Overrides\Model;
use Modules\ERP\Casts\ProjectStatus;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Enums\ERPTables;
use Modules\ERP\Support\ConnectionScopedModels;
use Override;

/**
 * @property int $id
 * @property int $company_id
 * @property int $party_id
 * @property int|null $quotation_id
 * @property string $name The name of the project
 * @property string|null $description The description of the project
 * @property ProjectStatus $status The status of the project
 * @property int $version The revision number of the project
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property bool|null $is_deleted Whether the entity is deleted
 * @property \Carbon\CarbonImmutable|null $locked_at The date and time when the entity was locked
 * @property int|null $locked_user_id The user who locked the entity
 * @property \Carbon\CarbonImmutable|null $locked_until The moment the lock expires; null means it never does
 * @property \Carbon\CarbonImmutable $valid_from
 * @property \Carbon\CarbonImmutable|null $valid_to
 */
final class Project extends Model implements ILockableModel, IValidatableModel
{
    use BelongsToCompany;
    use HasLocks;
    use HasValidity;

    /**
     * @var string
     */
    #[Override]
    protected $table = ERPTables::Projects->value;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'party_id',
        'quotation_id',
        'name',
        'description',
        'status',
        'version',
    ];

    /**
     * @return BelongsTo<Party, $this>
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * @return HasMany<SalesOrder, $this>
     */
    public function sales_orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function time_entries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules['create'] = array_merge($rules['create'], [
            'party_id' => ['required', 'integer', 'exists:' . ERPTables::Parties->value . ',id'],
            'quotation_id' => ['nullable', 'integer', 'exists:' . ERPTables::Quotations->value . ',id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', ProjectStatus::validationRule()],
            'version' => ['sometimes', 'integer', 'min:0', 'max:255'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'party_id' => ['sometimes', 'integer', 'exists:' . ERPTables::Parties->value . ',id'],
            'quotation_id' => ['nullable', 'integer', 'exists:' . ERPTables::Quotations->value . ',id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', ProjectStatus::validationRule()],
            'version' => ['sometimes', 'integer', 'min:0', 'max:255'],
        ]);

        return $rules;
    }

    protected static function booted(): void
    {
        self::saving(static function (Project $project): void {
            if ($project->exists && $project->getOriginal('locked_at') !== null) {
                $business_changes = array_diff(array_keys($project->getDirty()), [
                    'locked_at',
                    'locked_user_id',
                    'updated_at',
                ]);

                if ($business_changes !== []) {
                    throw ValidationException::withMessages([
                        'project' => ['Projects linked to an operational sales order are locked.'],
                    ]);
                }
            }

            if ($project->getAttribute('party_id') === null) {
                return;
            }

            $party = ConnectionScopedModels::for($project)
                ->query(Party::class)
                ->whereKey($project->party_id)->first();

            if ($party !== null && ! $party->is_customer) {
                throw ValidationException::withMessages([
                    'party_id' => ['The selected party must be a customer.'],
                ]);
            }
        });

        self::deleting(static function (Project $project): void {
            if ($project->isLocked()) {
                throw ValidationException::withMessages([
                    'project' => ['Locked projects cannot be deleted.'],
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'version' => 'integer',
        ];
    }
}
