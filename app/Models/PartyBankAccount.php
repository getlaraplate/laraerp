<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\IsPartOfParent;
use Modules\Core\Overrides\Model;
use Modules\CRM\Enums\CRMTables;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Concerns\ProxiesCounterpartyBankAccount;
use Modules\ERP\Enums\ERPTables;
use Override;

/**
 * The link between a party (one company) and a bank account of its counterparty: which account the company
 * uses by default and the direct debit mandate it holds. The account data is proxied from the counterparty.
 */
final class PartyBankAccount extends Model implements IsPartOfParent
{
    use BelongsToCompany;
    use ProxiesCounterpartyBankAccount;

    /**
     * @var string
     */
    #[Override]
    protected $table = ERPTables::PartyBankAccounts->value;

    #[Override]
    protected $fillable = [
        'company_id',
        'party_id',
        'direct_debit_mandate_reference',
        'direct_debit_mandate_signed_on',
        'direct_debit_mandate_scheme',
        'is_default',
    ];

    /**
     * The relation to the record this one only exists inside, whose visibility it inherits.
     */
    #[Override]
    public function parentRelation(): string
    {
        return 'party';
    }

    /**
     * @return BelongsTo<Party, $this>
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules['create'] = array_merge($rules['create'], [
            'company_id' => ['required', 'integer', 'exists:' . ERPTables::Companies->value . ',id'],
            'party_id' => ['required', 'integer', 'exists:' . ERPTables::Parties->value . ',id'],
            'counterparty_bank_account_id' => ['required', 'integer', 'exists:' . CRMTables::CounterpartyBankAccounts->value . ',id'],
            'direct_debit_mandate_reference' => ['nullable', 'string', 'max:35'],
            'direct_debit_mandate_signed_on' => ['nullable', 'date'],
            'direct_debit_mandate_scheme' => ['nullable', 'string', 'max:16'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'direct_debit_mandate_reference' => ['nullable', 'string', 'max:35'],
            'direct_debit_mandate_signed_on' => ['nullable', 'date'],
            'direct_debit_mandate_scheme' => ['nullable', 'string', 'max:16'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        return $rules;
    }

    protected function casts(): array
    {
        return [
            'direct_debit_mandate_signed_on' => 'date',
            'is_default' => 'boolean',
        ];
    }
}
