<?php

declare(strict_types=1);

use Modules\CRM\Models\Counterparty;
use Modules\CRM\Models\CounterpartyBankAccount;
use Modules\ERP\Models\Company;
use Modules\ERP\Models\Party;
use Modules\ERP\Models\PartyBankAccount;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function sharedCounterpartyAccount(Counterparty $counterparty): CounterpartyBankAccount
{
    return CounterpartyBankAccount::query()->create([
        'counterparty_id' => $counterparty->getKey(),
        'beneficiary_name' => 'Shared Beneficiary',
        'iban' => 'IT60X0542811101000000123456',
        'currency' => 'EUR',
    ]);
}

it('shares one bank account between the parties of two companies', function (): void {
    $counterparty = Counterparty::factory()->create();
    $account = sharedCounterpartyAccount($counterparty);
    $party_a = Party::factory()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => Company::factory()->create()->getKey()]);
    $party_b = Party::factory()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => Company::factory()->create()->getKey()]);

    PartyBankAccount::query()->create(['company_id' => $party_a->company_id, 'party_id' => $party_a->getKey(), 'counterparty_bank_account_id' => $account->getKey(), 'is_default' => true]);
    PartyBankAccount::query()->create(['company_id' => $party_b->company_id, 'party_id' => $party_b->getKey(), 'counterparty_bank_account_id' => $account->getKey(), 'is_default' => false]);

    expect(CounterpartyBankAccount::query()->count())->toBe(1)
        ->and($party_a->bank_accounts()->firstOrFail()->iban)->toBe('IT60X0542811101000000123456')
        ->and($party_b->bank_accounts()->firstOrFail()->iban)->toBe('IT60X0542811101000000123456');
});

it('keeps the mandate and the default flag per company', function (): void {
    $counterparty = Counterparty::factory()->create();
    $account = sharedCounterpartyAccount($counterparty);
    $party_a = Party::factory()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => Company::factory()->create()->getKey()]);
    $party_b = Party::factory()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => Company::factory()->create()->getKey()]);

    $link_a = PartyBankAccount::query()->create(['company_id' => $party_a->company_id, 'party_id' => $party_a->getKey(), 'counterparty_bank_account_id' => $account->getKey(), 'is_default' => true, 'direct_debit_mandate_reference' => 'MANDATE-A']);
    $link_b = PartyBankAccount::query()->create(['company_id' => $party_b->company_id, 'party_id' => $party_b->getKey(), 'counterparty_bank_account_id' => $account->getKey(), 'is_default' => false]);

    expect($link_a->fresh()->is_default)->toBeTrue()
        ->and($link_a->fresh()->direct_debit_mandate_reference)->toBe('MANDATE-A')
        ->and($link_b->fresh()->is_default)->toBeFalse()
        ->and($link_b->fresh()->direct_debit_mandate_reference)->toBeNull();
});

it('creates the counterparty bank account from the identity attributes given to the party link', function (): void {
    $party = Party::factory()->create();

    $link = PartyBankAccount::query()->create([
        'company_id' => $party->company_id,
        'party_id' => $party->getKey(),
        'beneficiary_name' => 'Direct Beneficiary',
        'iban' => 'de89 3704 0044 0532 0130 00',
        'currency' => 'eur',
        'is_default' => true,
    ]);

    $account = CounterpartyBankAccount::query()->firstOrFail();

    expect($link->counterparty_bank_account_id)->toBe($account->getKey())
        ->and($account->counterparty_id)->toBe($party->counterparty_id)
        ->and($link->fresh()->iban)->toBe('DE89370400440532013000')
        ->and($link->fresh()->beneficiary_name)->toBe('Direct Beneficiary')
        ->and($link->fresh()->is_active)->toBeTrue();
});

it('reuses a soft deleted counterparty bank account with the same iban', function (): void {
    $party = Party::factory()->create();
    $account = CounterpartyBankAccount::query()->create([
        'counterparty_id' => $party->counterparty_id,
        'beneficiary_name' => 'Old Beneficiary',
        'iban' => 'IT60X0542811101000000123456',
        'currency' => 'EUR',
    ]);
    $account->delete();

    $link = PartyBankAccount::query()->create([
        'company_id' => $party->company_id,
        'party_id' => $party->getKey(),
        'beneficiary_name' => 'New Beneficiary',
        'iban' => 'it60 x054 2811 1010 0000 0123 456',
        'currency' => 'EUR',
    ]);

    expect($link->counterparty_bank_account_id)->toBe($account->getKey())
        ->and(CounterpartyBankAccount::query()->withTrashed()->count())->toBe(1)
        ->and($account->fresh()->trashed())->toBeFalse();
});
