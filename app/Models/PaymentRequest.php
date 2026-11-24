<?php

declare(strict_types=1);

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Core\Overrides\Model;
use Modules\ERP\Casts\PaymentRequestStatus;
use Modules\ERP\Concerns\BelongsToCompany;
use Modules\ERP\Enums\ERPTables;
use Override;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $party_id
 * @property int|null $user_id
 * @property int|null $partner_pool_id
 * @property int|null $pool_transaction_id
 * @property numeric-string $amount
 * @property string $currency
 * @property \Carbon\CarbonImmutable|null $due_on
 * @property PaymentRequestStatus $status
 * @property string $provider_code
 * @property string|null $external_id
 * @property string|null $checkout_url
 * @property \Carbon\CarbonImmutable|null $sent_at
 * @property \Carbon\CarbonImmutable|null $paid_at
 * @property \Carbon\CarbonImmutable|null $cancelled_at
 * @property string|null $description
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property bool|null $is_deleted Whether the entity is deleted
 */
final class PaymentRequest extends Model
{
    use BelongsToCompany;

    #[Override]
    protected $table = ERPTables::PaymentRequests->value;

    #[Override]
    protected $fillable = [
        'company_id', 'party_id', 'user_id', 'partner_pool_id', 'pool_transaction_id',
        'amount', 'currency', 'due_on', 'status', 'provider_code', 'external_id',
        'checkout_url', 'provider_payload', 'sent_at', 'paid_at', 'cancelled_at', 'description',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner_pool(): BelongsTo
    {
        return $this->belongsTo(PartnerPool::class);
    }

    public function pool_transaction(): BelongsTo
    {
        return $this->belongsTo(PoolTransaction::class);
    }

    #[Override]
    public function getRules(): array
    {
        $rules = parent::getRules();
        $attributes = [
            'company_id' => ['required', 'integer', 'exists:' . ERPTables::Companies->value . ',id'],
            'party_id' => ['nullable', 'integer', 'required_without:user_id', 'prohibits:user_id'],
            'user_id' => ['nullable', 'integer', 'required_without:party_id', 'prohibits:party_id'],
            'partner_pool_id' => ['nullable', 'integer', 'exists:' . ERPTables::PartnerPools->value . ',id'],
            'pool_transaction_id' => ['nullable', 'integer', 'exists:' . ERPTables::PoolTransactions->value . ',id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3'],
            'due_on' => ['nullable', 'date'],
            'status' => ['required', 'string', PaymentRequestStatus::validationRule()],
            'provider_code' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
        ];
        $rules['create'] = array_merge($rules['create'], $attributes);
        $rules['update'] = array_merge($rules['update'], $attributes);

        return $rules;
    }

    protected static function booted(): void
    {
        self::saving(static function (PaymentRequest $request): void {
            if (($request->party_id === null) === ($request->user_id === null)) {
                throw ValidationException::withMessages(['recipient' => ['Select exactly one party or internal user.']]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4', 'due_on' => 'immutable_date', 'status' => PaymentRequestStatus::class,
            'provider_payload' => 'array', 'sent_at' => 'immutable_datetime', 'paid_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }
}
