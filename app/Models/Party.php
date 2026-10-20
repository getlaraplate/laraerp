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
use Modules\Core\Models\Concerns\HasActivation;
use Modules\Core\Overrides\Model;
use Modules\CRM\Enums\CRMTables;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Concerns\ProxiesCounterparty;
use Modules\ERP\Database\Factories\PartyFactory;
use Modules\ERP\Enums\ERPTables;
use Modules\ERP\Models\Pivot\Contactable;
use Modules\ERP\Observers\PartyObserver;
use Override;

#[ObservedBy([PartyObserver::class])]
final class Party extends Model implements IActivatableModel
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
     * @return BelongsToMany<Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, ERPTables::Contactables->value, 'party_id', 'contact_id')
            ->using(Contactable::class)
            ->withTimestamps();
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
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
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

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules['create'] = array_merge($rules['create'], [
            'counterparty_id' => ['required', 'integer', 'exists:' . CRMTables::Counterparties->value . ',id'],
        ]);

        return $rules;
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
}
