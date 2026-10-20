<?php

declare(strict_types=1);

use Modules\CRM\Models\Counterparty;
use Modules\ERP\Models\Company;
use Modules\ERP\Models\Party;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function partyNamed(string $name, array $counterparty = [], array $party = []): Party
{
    return Party::factory()->create([
        'counterparty_id' => Counterparty::factory()->create(['name' => $name, ...$counterparty]),
        ...$party,
    ]);
}

it('orders party options by the counterparty name', function (): void {
    partyNamed('Charlie S.p.A.');
    partyNamed('Alpha S.r.l.');
    partyNamed('Bravo S.n.c.');

    expect(array_values(Party::optionLabels(Party::query())))->toBe(['Alpha S.r.l.', 'Bravo S.n.c.', 'Charlie S.p.A.']);
});

it('searches party options by name and by vat number', function (): void {
    $alpha = partyNamed('Alpha S.r.l.', ['vat_number' => 'IT00000000001']);
    partyNamed('Bravo S.n.c.', ['vat_number' => 'IT00000000002']);

    expect(Party::optionLabels(Party::query(), 'alph'))->toBe([(int) $alpha->getKey() => 'Alpha S.r.l.'])
        ->and(array_keys(Party::optionLabels(Party::query(), '00000000001')))->toBe([(int) $alpha->getKey()])
        ->and(Party::optionLabels(Party::query(), 'nothing like this'))->toBe([]);
});

it('treats the search term as literal text, not as a like pattern', function (): void {
    partyNamed('Alpha S.r.l.');

    expect(Party::optionLabels(Party::query(), '%'))->toBe([]);
});

it('combines the role scopes with search and ordering', function (): void {
    $company = Company::factory()->create();
    $customer = partyNamed('Zeta Customer', party: ['company_id' => $company->getKey(), 'is_customer' => true, 'is_supplier' => false]);
    partyNamed('Alpha Supplier', party: ['company_id' => $company->getKey(), 'is_customer' => false, 'is_supplier' => true]);

    expect(Party::optionLabels(Party::query()->customers()))->toBe([(int) $customer->getKey() => 'Zeta Customer'])
        ->and(array_values(Party::optionLabels(Party::query()->suppliers(), 'alpha')))->toBe(['Alpha Supplier']);
});
