<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Core\Contracts\IActivatableModel;
use Modules\Core\Contracts\IMountsRelatedAttributes;
use Modules\Core\Models\Concerns\HasActivation;
use Modules\Core\Overrides\Model;
use Modules\CRM\Enums\CRMTables;
use Modules\CRM\Models\Contact;
use Modules\CRM\Models\Counterparty;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Concerns\ProxiesCounterparty;
use Modules\ERP\Database\Factories\PartyFactory;
use Modules\ERP\Enums\ERPTables;
use Modules\ERP\Observers\PartyObserver;
use Override;

#[ObservedBy([PartyObserver::class])]
final class Party extends Model implements IActivatableModel, IMountsRelatedAttributes
{
    use BelongsToCompany;
    use HasActivation;
    use ProxiesCounterparty;

    /**
     * @var string
     */
    #[Override]
    protected $table = ERPTables::Parties->value;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'is_customer',
        'is_supplier',
    ];

    /**
     * The contacts of the party's counterparty (the contact list is shared by every company).
     *
     * @return BelongsToMany<Contact, Counterparty>
     */
    public function contacts(): BelongsToMany
    {
        return $this->counterparty->contacts();
    }

    /**
     * @return HasManyThrough<Task, Project, $this>
     */
    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Project::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Quotation, $this>
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * @return HasMany<SalesOrder, $this>
     */
    public function sales_orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * @return HasMany<PartyPriceRule, $this>
     */
    public function price_rules(): HasMany
    {
        return $this->hasMany(PartyPriceRule::class);
    }

    /**
     * @return HasMany<PartyBankAccount, $this>
     */
    public function bank_accounts(): HasMany
    {
        return $this->hasMany(PartyBankAccount::class);
    }

    #[Override]
    public function mountedRelation(): string
    {
        return 'counterparty';
    }

    #[Override]
    public function isMountedAttribute(string $key): bool
    {
        return in_array($key, self::counterpartyAttributes(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        $rules = parent::getRules();
        $identity = [
            'tax_id' => ['nullable', 'string', 'max:32'],
            'vat_number' => ['nullable', 'string', 'max:32'],
            'fiscal_country' => ['nullable', 'string', 'size:2'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:16'],
            'city' => ['nullable', 'string', 'max:128'],
            'province' => ['nullable', 'string', 'max:8'],
            'country' => ['nullable', 'string', 'size:2'],
            'einvoice_recipient_code' => ['nullable', 'string', 'max:7'],
            'einvoice_pec_email' => ['nullable', 'email', 'max:255'],
        ];
        $rules['create'] = array_merge($rules['create'], $identity, [
            'counterparty_id' => ['nullable', 'integer', 'exists:' . CRMTables::Counterparties->value . ',id'],
            'name' => ['required_without:counterparty_id', 'string', 'max:255'],
        ]);
        $rules['update'] = array_merge($rules['update'], $identity, [
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        return $rules;
    }

    /**
     * Whether the counterparty of this party is another company of the group (D29): the sale or purchase is
     * between two companies of the same installation.
     */
    public function isIntercompany(): bool
    {
        if ($this->counterparty_id === null) {
            return false;
        }

        return Company::query()
            ->where('counterparty_id', $this->counterparty_id)
            ->whereKeyNot($this->company_id)
            ->exists();
    }

    /**
     * @return Factory<self>
     */
    #[Override]
    protected static function newFactory(): Factory
    {
        return PartyFactory::new();
    }

    /**
     * @param  Builder<Party>  $builder
     * @return Builder<Party>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function customers(Builder $builder): Builder
    {
        return $builder->where($builder->getModel()->qualifyColumn('is_customer'), true);
    }

    /**
     * @param  Builder<Party>  $builder
     * @return Builder<Party>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function suppliers(Builder $builder): Builder
    {
        return $builder->where($builder->getModel()->qualifyColumn('is_supplier'), true);
    }

    /**
     * Parties whose counterparty is another company of the group.
     *
     * @param  Builder<Party>  $builder
     * @return Builder<Party>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function intercompany(Builder $builder): Builder
    {
        $companies = new Company;

        return $builder->whereExists(
            static fn ($query) => $query->selectRaw('1')
                ->from($companies->getTable())
                ->whereColumn($companies->qualifyColumn('counterparty_id'), $builder->getModel()->qualifyColumn('counterparty_id'))
                ->whereColumn($companies->qualifyColumn('id'), '!=', $builder->getModel()->qualifyColumn('company_id'))
                ->whereNull($companies->qualifyColumn('deleted_at')),
        );
    }
}
