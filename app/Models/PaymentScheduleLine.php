<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Contracts\IsPartOfParent;
use Modules\Core\Overrides\Model;
use Modules\ERP\Casts\PaymentScheduleStatus;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Enums\ERPTables;
use Override;

/**
 * @property int $id
 * @property int $company_id
 * @property int $invoice_id
 * @property \Carbon\CarbonImmutable $due_date
 * @property numeric-string $amount_doc Amount in the document currency
 * @property string $currency_doc ISO 4217 currency code of the document
 * @property numeric-string $amount_local Amount in the company functional currency (basis for journal balancing)
 * @property string $currency_local ISO 4217 functional currency (Company.default_currency)
 * @property numeric-string $fx_rate Multiplier from doc to local (1 when both currencies match)
 * @property numeric-string $paid_amount_doc
 * @property numeric-string $paid_amount_local
 * @property PaymentScheduleStatus $status
 * @property \Carbon\CarbonImmutable|null $paid_at
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property bool|null $is_deleted Whether the entity is deleted
 */
final class PaymentScheduleLine extends Model implements IsPartOfParent
{
    use BelongsToCompany;

    /**
     * @var string
     */
    #[Override]
    protected $table = ERPTables::PaymentScheduleLines->value;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'company_id',
        'invoice_id',
        'due_date',
        'amount_doc',
        'currency_doc',
        'amount_local',
        'currency_local',
        'fx_rate',
        'paid_amount_doc',
        'paid_amount_local',
        'status',
        'paid_at',
    ];

    /**
     * The relation to the record this one only exists inside, whose visibility it inherits.
     */
    #[Override]
    public function parentRelation(): string
    {
        return 'invoice';
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsToMany<Payment, $this>
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, ERPTables::PaymentAllocations->value)
            ->withPivot(['allocated_amount_doc', 'allocated_amount_local'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PaymentRunLine, $this>
     */
    public function payment_run_lines(): HasMany
    {
        return $this->hasMany(PaymentRunLine::class);
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
            'invoice_id' => ['required', 'integer', 'exists:' . ERPTables::Invoices->value . ',id'],
            'due_date' => ['required', 'date'],
            'amount_doc' => ['required', 'numeric', 'min:0.0001'],
            'currency_doc' => ['required', 'string', 'size:3'],
            'amount_local' => ['required', 'numeric', 'min:0.0001'],
            'currency_local' => ['required', 'string', 'size:3'],
            'fx_rate' => ['required', 'numeric', 'min:0'],
            'paid_amount_doc' => ['numeric', 'min:0'],
            'paid_amount_local' => ['numeric', 'min:0'],
            'status' => ['string', PaymentScheduleStatus::validationRule()],
            'paid_at' => ['nullable', 'date'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'invoice_id' => ['sometimes', 'integer', 'exists:' . ERPTables::Invoices->value . ',id'],
            'due_date' => ['sometimes', 'date'],
            'amount_doc' => ['sometimes', 'numeric', 'min:0.0001'],
            'currency_doc' => ['sometimes', 'string', 'size:3'],
            'amount_local' => ['sometimes', 'numeric', 'min:0.0001'],
            'currency_local' => ['sometimes', 'string', 'size:3'],
            'fx_rate' => ['sometimes', 'numeric', 'min:0'],
            'paid_amount_doc' => ['numeric', 'min:0'],
            'paid_amount_local' => ['numeric', 'min:0'],
            'status' => ['string', PaymentScheduleStatus::validationRule()],
            'paid_at' => ['nullable', 'date'],
        ]);

        return $rules;
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount_doc' => 'decimal:4',
            'amount_local' => 'decimal:4',
            'fx_rate' => 'decimal:8',
            'paid_amount_doc' => 'decimal:4',
            'paid_amount_local' => 'decimal:4',
            'status' => PaymentScheduleStatus::class,
            'paid_at' => 'immutable_datetime',
        ];
    }
}
