<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Modules\CRM\Models\Counterparty;
use Modules\ERP\Models\Company;
use Modules\ERP\Models\Party;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('links a company to the existing counterparty with its VAT number', function (): void {
    $existing = Counterparty::factory()->create(['fiscal_country' => 'IT', 'vat_number' => 'IT01234567890']);
    $company = Company::factory()->create(['tax_id' => '01234567890', 'fiscal_country' => 'IT']);

    expect($company->ensureCounterparty()->is($existing))->toBeTrue()
        ->and($company->fresh()->counterparty_id)->toBe($existing->getKey())
        ->and(Counterparty::query()->count())->toBe(1);
});

it('creates the counterparty of a company from its legal identity when none matches', function (): void {
    $company = Company::factory()->create([
        'tax_id' => 'IT09876543210',
        'fiscal_country' => 'IT',
        'legal_name' => 'Alfa S.r.l.',
        'legal_city' => 'Torino',
    ]);

    $counterparty = $company->ensureCounterparty();

    expect($counterparty->name)->toBe('Alfa S.r.l.')
        ->and($counterparty->vat_number)->toBe('IT09876543210')
        ->and($counterparty->city)->toBe('Torino')
        ->and($company->fresh()->counterparty_id)->toBe($counterparty->getKey());
});

it('refuses to give a counterparty to a company without a tax id', function (): void {
    $company = Company::factory()->create(['tax_id' => null]);

    expect(fn () => $company->ensureCounterparty())->toThrow(ValidationException::class);
});

it('recognises the parties whose counterparty is another company of the group', function (): void {
    $alfa = Company::factory()->create();
    $beta = Company::factory()->create();
    $beta_identity = $beta->ensureCounterparty();
    $alfa_identity = $alfa->ensureCounterparty();

    $intercompany = Party::factory()->create(['company_id' => $alfa->getKey(), 'counterparty_id' => $beta_identity->getKey()]);
    $ordinary = Party::factory()->create(['company_id' => $alfa->getKey()]);
    $own_identity = Party::factory()->create(['company_id' => $alfa->getKey(), 'counterparty_id' => $alfa_identity->getKey()]);

    expect($intercompany->isIntercompany())->toBeTrue()
        ->and($ordinary->isIntercompany())->toBeFalse()
        ->and($own_identity->isIntercompany())->toBeFalse()
        ->and(Party::query()->intercompany()->pluck('id')->all())->toBe([$intercompany->getKey()]);
});

it('refuses to delete a counterparty that is the identity of a company', function (): void {
    $company = Company::factory()->create();
    $counterparty = $company->ensureCounterparty();

    expect(fn () => $counterparty->delete())->toThrow(ValidationException::class);
});
