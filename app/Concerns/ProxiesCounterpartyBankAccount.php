<?php

declare(strict_types=1);

namespace Modules\ERP\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CRM\Models\CounterpartyBankAccount;
use Modules\ERP\Models\Party;
use Modules\ERP\Scopes\BelongsToCompanyScope;
use Override;

/**
 * Makes a party bank account read and write the IBAN, BIC, beneficiary and currency of the
 * {@see CounterpartyBankAccount} it points to as if they were its own. The account itself belongs to the
 * counterparty and is stored once; this model keeps only what differs per company (default flag,
 * direct debit mandate).
 *
 * On save without a `counterparty_bank_account_id`, the account of the party's counterparty with the same
 * IBAN is reused, or created. With one, changed attributes are written through to the shared account.
 *
 * @property int|string|null $counterparty_bank_account_id
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait ProxiesCounterpartyBankAccount
{
    /**
     * @var array<string, mixed>
     */
    private array $pendingBankAccountAttributes = [];

    /**
     * @return list<string>
     */
    public static function bankAccountAttributes(): array
    {
        return ['beneficiary_name', 'iban', 'bic', 'currency', 'is_active'];
    }

    public function initializeProxiesCounterpartyBankAccount(): void
    {
        $this->fillable = array_values(array_unique([...$this->fillable, 'counterparty_bank_account_id', ...self::bankAccountAttributes()]));
    }

    /**
     * @return BelongsTo<CounterpartyBankAccount, $this>
     */
    public function counterparty_bank_account(): BelongsTo
    {
        return $this->belongsTo(CounterpartyBankAccount::class)->withTrashed();
    }

    /**
     * @param  array<int, static>  $models
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    #[Override]
    public function newCollection(array $models = [])
    {
        $collection = parent::newCollection($models);

        $unloaded = $collection->filter(
            static fn (mixed $model): bool => $model instanceof self
                && array_key_exists('counterparty_bank_account_id', $model->getAttributes())
                && ! $model->relationLoaded('counterparty_bank_account'),
        );

        if ($unloaded->isNotEmpty()) {
            $unloaded->load('counterparty_bank_account');
        }

        return $collection;
    }

    #[Override]
    public function getAttribute($key)
    {
        if (is_string($key) && in_array($key, self::bankAccountAttributes(), true)) {
            return array_key_exists($key, $this->pendingBankAccountAttributes)
                ? $this->pendingBankAccountAttributes[$key]
                : $this->counterparty_bank_account?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    #[Override]
    public function setAttribute($key, $value)
    {
        if (is_string($key) && in_array($key, self::bankAccountAttributes(), true)) {
            $this->pendingBankAccountAttributes[$key] = $value;

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
        $account = [];

        foreach (self::bankAccountAttributes() as $attribute) {
            $value = $this->getAttribute($attribute);
            $account[$attribute] = $value instanceof BackedEnum ? $value->value : $value;
        }

        return [...parent::attributesToArray(), ...$account];
    }

    #[Override]
    public function save(array $options = []): bool
    {
        return $this->getConnection()->transaction(function () use ($options): bool {
            $this->resolveCounterpartyBankAccount();

            return parent::save($options);
        });
    }

    private function resolveCounterpartyBankAccount(): void
    {
        $pending = $this->pendingBankAccountAttributes;

        if (($this->attributes['counterparty_bank_account_id'] ?? null) !== null) {
            $account = $this->counterparty_bank_account;

            if ($account instanceof CounterpartyBankAccount && $pending !== []) {
                $account->fill($pending);

                if ($account->isDirty()) {
                    $account->save();
                }
            }
        } else {
            $party = Party::query()->withoutGlobalScope(BelongsToCompanyScope::class)->findOrFail($this->getAttribute('party_id'));
            $iban = CounterpartyBankAccount::normalizeIban((string) ($pending['iban'] ?? ''));

            $account = ($iban === '' ? null : CounterpartyBankAccount::query()
                ->withTrashed()
                ->where('counterparty_id', $party->counterparty_id)
                ->where('iban', $iban)
                ->first())
                ?? CounterpartyBankAccount::query()->create([...$pending, 'counterparty_id' => $party->counterparty_id]);

            if ($account->trashed()) {
                $account->restore();
            }

            parent::setAttribute('counterparty_bank_account_id', $account->getKey());
            $this->setRelation('counterparty_bank_account', $account);
        }

        $this->pendingBankAccountAttributes = [];
    }
}
