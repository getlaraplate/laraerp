<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\CRM\Models\Counterparty;
use Modules\ERP\Models\Company;
use Modules\ERP\Models\Party;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a counterparty on first save', function (): void {
    $company = Company::factory()->create();

    $party = Party::query()->create([
        'company_id' => $company->getKey(),
        'name' => 'Beta S.r.l.',
        'vat_number' => 'IT01111111111',
        'fiscal_country' => 'IT',
    ]);

    expect($party->counterparty_id)->not->toBeNull()
        ->and(Counterparty::query()->count())->toBe(1)
        ->and($party->name)->toBe('Beta S.r.l.')
        ->and($party->counterparty->vat_number)->toBe('IT01111111111');
});

it('reads and writes identity attributes through the party', function (): void {
    $party = Party::factory()->create();

    $party->name = 'Renamed S.p.A.';
    $party->city = 'Torino';
    $party->save();

    $counterparty = Counterparty::query()->findOrFail($party->counterparty_id);

    expect($counterparty->name)->toBe('Renamed S.p.A.')
        ->and($counterparty->city)->toBe('Torino')
        ->and($party->fresh()->name)->toBe('Renamed S.p.A.')
        ->and($party->fresh()->attributesToArray())->toHaveKeys(['name', 'vat_number', 'company_id']);
});

it('attaches to the existing counterparty with the same vat number and keeps its stored name', function (): void {
    $existing = Counterparty::factory()->create(['name' => 'Stored Name', 'fiscal_country' => 'IT', 'vat_number' => 'IT02222222222']);
    $company = Company::factory()->create();

    $party = Party::query()->create([
        'company_id' => $company->getKey(),
        'name' => 'Different Name',
        'fiscal_country' => 'IT',
        'vat_number' => 'IT02222222222',
    ]);

    expect($party->counterparty_id)->toBe($existing->getKey())
        ->and(Counterparty::query()->count())->toBe(1)
        ->and($party->fresh()->name)->toBe('Stored Name');
});

it('does not attach two parties with no identifier to one counterparty', function (): void {
    $company = Company::factory()->create();

    $first = Party::query()->create(['company_id' => $company->getKey(), 'name' => 'Anonymous One']);
    $second = Party::query()->create(['company_id' => $company->getKey(), 'name' => 'Anonymous Two']);

    expect($first->counterparty_id)->not->toBe($second->counterparty_id)
        ->and(Counterparty::query()->count())->toBe(2);
});

it('allows one party per company for a counterparty but not two', function (): void {
    $counterparty = Counterparty::factory()->create();
    $company_a = Company::factory()->create();
    $company_b = Company::factory()->create();

    Party::factory()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => $company_a->getKey()]);
    Party::factory()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => $company_b->getKey()]);

    expect(fn () => Party::factory()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => $company_a->getKey()]))
        ->toThrow(QueryException::class);
});

it('refuses a party for an organisational counterparty', function (): void {
    $group = Counterparty::factory()->create(['vat_number' => null, 'tax_id' => null]);
    Counterparty::factory()->create(['parent_counterparty_id' => $group->getKey()]);
    $company = Company::factory()->create();

    expect(fn () => Party::factory()->create(['counterparty_id' => $group->getKey(), 'company_id' => $company->getKey()]))
        ->toThrow(ValidationException::class);

    $childless = Counterparty::factory()->create(['vat_number' => null, 'tax_id' => null]);

    expect(Party::factory()->create(['counterparty_id' => $childless->getKey(), 'company_id' => $company->getKey()])->exists)->toBeTrue();
});

it('refuses to delete a counterparty that has a party', function (): void {
    $party = Party::factory()->create();
    $counterparty = Counterparty::query()->findOrFail($party->counterparty_id);

    expect(fn () => $counterparty->delete())->toThrow(ValidationException::class);

    $party->delete();

    expect($counterparty->fresh()->delete())->toBeTrue();
});

it('recomputes the counterparty flags when a party is saved or deleted', function (): void {
    $counterparty = Counterparty::factory()->create();
    $company_a = Company::factory()->create();
    $company_b = Company::factory()->create();

    $customer = Party::factory()->customer()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => $company_a->getKey()]);

    expect($counterparty->fresh()->is_customer)->toBeTrue()
        ->and($counterparty->fresh()->is_supplier)->toBeFalse();

    Party::factory()->supplier()->create(['counterparty_id' => $counterparty->getKey(), 'company_id' => $company_b->getKey()]);

    expect($counterparty->fresh()->is_supplier)->toBeTrue();

    $customer->delete();

    expect($counterparty->fresh()->is_customer)->toBeFalse()
        ->and($counterparty->fresh()->is_supplier)->toBeTrue();
});

it('loads parties with their counterparty without one query per row', function (): void {
    Party::factory()->count(10)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $names = Party::query()->get()->map(fn (Party $party): string => $party->name)->all();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($names)->toHaveCount(10)->and($queries)->toBeLessThanOrEqual(3);
});

it('matches and restores a soft deleted counterparty with the same vat number', function (): void {
    $trashed = Counterparty::factory()->create(['fiscal_country' => 'IT', 'vat_number' => 'IT05555555555']);
    $trashed->delete();
    $company = Company::factory()->create();

    $party = Party::query()->create(['company_id' => $company->getKey(), 'name' => 'Whatever', 'fiscal_country' => 'IT', 'vat_number' => 'IT05555555555']);

    expect($party->counterparty_id)->toBe($trashed->getKey())
        ->and(Counterparty::query()->withTrashed()->where('vat_number', 'IT05555555555')->count())->toBe(1)
        ->and($trashed->fresh()->trashed())->toBeFalse();
});

it('restores the trashed counterparty when a trashed party is restored', function (): void {
    $party = Party::factory()->create();
    $counterparty = Counterparty::query()->findOrFail($party->counterparty_id);

    $party->delete();
    $counterparty->delete();
    $party->restore();

    expect($party->fresh()->trashed())->toBeFalse()
        ->and($counterparty->fresh()->trashed())->toBeFalse();
});

it('validates a create payload that carries identity attributes instead of a counterparty id', function (): void {
    $rules = (new Party)->getRules()['create'];
    $company = Company::factory()->create();

    $valid = Illuminate\Support\Facades\Validator::make(['company_id' => $company->getKey(), 'name' => 'Payload Co', 'vat_number' => 'IT09999999999'], $rules);
    $nameless = Illuminate\Support\Facades\Validator::make(['company_id' => $company->getKey(), 'vat_number' => 'IT09999999999'], $rules);

    expect($valid->passes())->toBeTrue()
        ->and($nameless->passes())->toBeFalse();
});

it('recomputes the flags of the counterparty a party moves away from', function (): void {
    $old = Counterparty::factory()->create();
    $new = Counterparty::factory()->create();
    $party = Party::factory()->customer()->create(['counterparty_id' => $old->getKey()]);

    expect($old->fresh()->is_customer)->toBeTrue();

    $party->counterparty_id = $new->getKey();
    $party->save();

    expect($old->fresh()->is_customer)->toBeFalse()
        ->and($new->fresh()->is_customer)->toBeTrue();
});

it('does not touch the counterparty aggregates when a party is saved without a relevant change', function (): void {
    $party = Party::factory()->customer()->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $party->update(['is_active' => false]);
    $statements = collect(DB::getQueryLog())->pluck('query')->implode(' | ');
    DB::disableQueryLog();

    expect($statements)->not->toContain('crm_counterparties')
        ->and($statements)->not->toContain('"is_customer" = ?');
});

it('refuses to give a parent to a counterparty that already has a party when the parent is organisational', function (): void {
    $group = Counterparty::factory()->create(['vat_number' => null, 'tax_id' => null]);
    Party::factory()->create(['counterparty_id' => $group->getKey()]);
    $child = Counterparty::factory()->create();

    $child->parent_counterparty_id = $group->getKey();

    expect(fn () => $child->save())->toThrow(ValidationException::class);

    $legal_parent = Counterparty::factory()->create();
    Party::factory()->create(['counterparty_id' => $legal_parent->getKey()]);
    $child->parent_counterparty_id = $legal_parent->getKey();

    expect($child->save())->toBeTrue();
});
