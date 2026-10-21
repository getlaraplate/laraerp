<?php

declare(strict_types=1);

namespace Modules\ERP\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Modules\CRM\Models\Counterparty;
use Override;

/**
 * Makes a model read and write the legal identity of its {@see Counterparty} as if the columns were
 * its own (`$party->name`, `$party->vat_number`), so code written before the identity moved to the
 * CRM keeps working.
 *
 * - The relation is loaded for every retrieved collection (`newCollection`), so a list of models costs one extra query, not one per row.
 * - On save without a `counterparty_id`, the model attaches to the counterparty with the same fiscal
 *   identity, or creates one; an attach never overwrites the stored attributes. A blank identifier
 *   never matches. With a `counterparty_id`, changed identity attributes are written through to the
 *   shared counterparty: there is one identity.
 * - SQL cannot see these attributes; searching and ordering go through the scopes below.
 *
 * @property int|string|null $counterparty_id
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait ProxiesCounterparty
{
    /**
     * @var array<string, mixed>
     */
    private array $pendingCounterpartyAttributes = [];

    /**
     * @return list<string>
     */
    public static function counterpartyAttributes(): array
    {
        return [
            'name',
            'first_name',
            'last_name',
            'kind',
            'tax_id',
            'vat_number',
            'fiscal_country',
            'address_line',
            'postal_code',
            'city',
            'province',
            'country',
            'einvoice_recipient_code',
            'einvoice_pec_email',
        ];
    }

    /**
     * Id to display name map of the matching parties, searched and ordered by the counterparty name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return array<int, string>
     */
    public static function optionLabels($query, ?string $search = null, int $limit = 50): array
    {
        if (filled($search)) {
            $query->searchIdentity($search);
        }

        return $query->orderByName()
            ->limit($limit)
            ->get()
            ->mapWithKeys(static fn (self $party): array => [(int) $party->getKey() => (string) $party->name])
            ->all();
    }

    public function initializeProxiesCounterparty(): void
    {
        $this->fillable = array_values(array_unique([...$this->fillable, 'counterparty_id', ...self::counterpartyAttributes()]));
    }

    /**
     * Load the counterparty of every retrieved row in one query. Done here rather than through `$with`
     * because a partial select (`value('is_customer')`, `select('id')`) carries no `counterparty_id`
     * and must stay readable.
     *
     * @param  array<int, static>  $models
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    #[Override]
    public function newCollection(array $models = [])
    {
        $collection = parent::newCollection($models);

        $unloaded = $collection->filter(
            static fn (mixed $model): bool => $model instanceof self
                && array_key_exists('counterparty_id', $model->getAttributes())
                && ! $model->relationLoaded('counterparty'),
        );

        if ($unloaded->isNotEmpty()) {
            $unloaded->load('counterparty');
        }

        return $collection;
    }

    /**
     * @return BelongsTo<Counterparty, $this>
     */
    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id')->withTrashed();
    }

    #[Override]
    public function getAttribute($key)
    {
        if (is_string($key) && in_array($key, self::counterpartyAttributes(), true)) {
            return array_key_exists($key, $this->pendingCounterpartyAttributes)
                ? $this->pendingCounterpartyAttributes[$key]
                : $this->counterparty?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    #[Override]
    public function setAttribute($key, $value)
    {
        if (is_string($key) && in_array($key, self::counterpartyAttributes(), true)) {
            $this->pendingCounterpartyAttributes[$key] = $value;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function attributesToArray()
    {
        $identity = [];

        foreach (self::counterpartyAttributes() as $attribute) {
            $value = $this->getAttribute($attribute);
            $identity[$attribute] = $value instanceof BackedEnum ? $value->value : $value;
        }

        return [...parent::attributesToArray(), ...$identity];
    }

    #[Override]
    public function save(array $options = []): bool
    {
        return $this->getConnection()->transaction(function () use ($options): bool {
            $this->resolveCounterparty();

            return parent::save($options);
        });
    }

    /**
     * Restrict the query to rows whose counterparty matches the term on name, VAT number or tax id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeSearchIdentity($query, string $term)
    {
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)) . '%';

        return $query->whereHas('counterparty', static function ($counterparties) use ($like): void {
            $counterparties->where(static function ($group) use ($like): void {
                $group->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("LOWER(vat_number) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("LOWER(tax_id) LIKE ? ESCAPE '!'", [$like]);
            });
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOrderByName($query, string $direction = 'asc')
    {
        return $query->orderBy(
            Counterparty::query()
                ->withTrashed()
                ->select('name')
                ->whereColumn((new Counterparty())->qualifyColumn('id'), $this->qualifyColumn('counterparty_id')),
            $direction,
        );
    }

    private function resolveCounterparty(): void
    {
        $pending = $this->pendingCounterpartyAttributes;

        if (($this->attributes['counterparty_id'] ?? null) !== null) {
            $counterparty = $this->counterparty;

            if ($counterparty instanceof Counterparty && $pending !== []) {
                $counterparty->fill($pending);

                if ($counterparty->isDirty()) {
                    $counterparty->save();
                }
            }
        } else {
            $counterparty = Counterparty::findByFiscalIdentity(
                $pending['fiscal_country'] ?? null,
                $pending['vat_number'] ?? null,
                $pending['tax_id'] ?? null,
            ) ?? Counterparty::query()->create($pending);

            if ($counterparty->trashed()) {
                $counterparty->restore();
            }

            parent::setAttribute('counterparty_id', $counterparty->getKey());
            $this->setRelation('counterparty', $counterparty);
        }

        if ($counterparty instanceof Counterparty && (! $this->exists || $this->isDirty('counterparty_id'))) {
            $this->assertCounterpartyCanHaveParty($counterparty);
        }

        $this->pendingCounterpartyAttributes = [];
    }

    private function assertCounterpartyCanHaveParty(Counterparty $counterparty): void
    {
        $has_identity = ! blank($counterparty->vat_number) || ! blank($counterparty->tax_id);

        if (! $has_identity && $counterparty->children()->exists()) {
            throw ValidationException::withMessages([
                'counterparty_id' => ['An organisational counterparty cannot have a party.'],
            ]);
        }
    }
}
