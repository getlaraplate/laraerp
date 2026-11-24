<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\IsPartOfParent;
use Modules\Core\Overrides\Model;
use Modules\ERP\Casts\BankStatementLineStatus;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Enums\ERPTables;
use Override;

/**
 * @property int $id
 * @property int $company_id
 * @property int $bank_statement_id
 * @property int|null $matched_payment_id
 * @property int|null $difference_journal_entry_id
 * @property \Carbon\CarbonImmutable $booked_at
 * @property \Carbon\CarbonImmutable|null $value_at
 * @property string|null $reference
 * @property string|null $description
 * @property numeric-string $amount_doc Amount in the document currency
 * @property string $currency_doc ISO 4217 currency code of the document
 * @property numeric-string $amount_local Amount in the company functional currency (basis for journal balancing)
 * @property string $currency_local ISO 4217 functional currency (Company.default_currency)
 * @property numeric-string $fx_rate Multiplier from doc to local (1 when both currencies match)
 * @property BankStatementLineStatus $status
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property bool|null $is_deleted Whether the entity is deleted
 * @property array<array-key, mixed>|null $raw_payload
 */
final class BankStatementLine extends Model implements IsPartOfParent
{
    use BelongsToCompany;

    /**
     * @var string
     */
    #[Override]
    protected $table = ERPTables::BankStatementLines->value;

    #[Override]
    protected $fillable = [
        'company_id',
        'bank_statement_id',
        'matched_payment_id',
        'difference_journal_entry_id',
        'booked_at',
        'value_at',
        'reference',
        'description',
        'amount_doc',
        'currency_doc',
        'amount_local',
        'currency_local',
        'fx_rate',
        'status',
        'raw_payload',
    ];

    /**
     * The relation to the record this one only exists inside, whose visibility it inherits.
     */
    #[Override]
    public function parentRelation(): string
    {
        return 'bank_statement';
    }

    /**
     * @return BelongsTo<BankStatement, $this>
     */
    public function bank_statement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function matched_payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'matched_payment_id');
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function difference_journal_entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'difference_journal_entry_id');
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules['create'] = array_merge($rules['create'], [
            'raw_payload' => ['nullable', 'array'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'raw_payload' => ['nullable', 'array'],
        ]);

        return $rules;
    }

    protected function casts(): array
    {
        return [
            'booked_at' => 'date',
            'value_at' => 'date',
            'amount_doc' => 'decimal:4',
            'amount_local' => 'decimal:4',
            'fx_rate' => 'decimal:8',
            'status' => BankStatementLineStatus::class,
            'raw_payload' => 'array',
        ];
    }
}
