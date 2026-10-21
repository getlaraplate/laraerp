<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CRM\Casts\OpportunityStatus;
use Modules\CRM\Database\Seeders\CRMDatabaseSeeder;
use Modules\CRM\Models\Entity;
use Modules\CRM\Models\Opportunity;
use Modules\CRM\Models\OpportunityStage;
use Modules\CRM\Models\Pivot\Presettable;
use Modules\ERP\Models\Company;
use Modules\ERP\Models\Party;
use Modules\ERP\Services\Reporting\SalesPipelineService;
use Modules\ERP\Tests\Stubs\OpportunityStatusDouble;
use Modules\ERP\Tests\Stubs\SalesPipelineOpportunityRowDouble;

uses(RefreshDatabase::class);

it('streams opportunity rows for pipeline aggregation', function (): void {
    $source = file_get_contents(base_path('Modules/ERP/app/Services/Reporting/SalesPipelineService.php'));

    expect($source)->not->toContain('->get([')
        ->and($source)->toContain('->lazy(500)');
});

it('creates buckets for unknown opportunity status values', function (): void {
    $legacy_row = new SalesPipelineOpportunityRowDouble(
        status: new OpportunityStatusDouble('archived'),
        expected_value_doc: '250.0000',
        expected_value_local: '200.0000',
        won_at: null,
        lost_at: null,
    );

    $result = new SalesPipelineService(
        opportunityProvider: static fn (): Collection => new Collection([$legacy_row]),
    )->generate();

    expect($result['by_status']['archived']['count'])->toBe(1)
        ->and($result['by_status']['archived']['expected_value_doc'])->toBe('250.0000')
        ->and($result['by_status']['archived']['expected_value_local'])->toBe('200.0000')
        ->and($result['total_count'])->toBe(1);
});

it('aggregates pipeline totals and status buckets for opportunities', function (): void {
    $this->seed(CRMDatabaseSeeder::class);

    $company = Company::factory()->create();
    $entity = Entity::query()->withoutGlobalScopes()->where('name', 'opportunity_stage')->firstOrFail();
    $presettable = Presettable::query()->where('entity_id', $entity->id)->firstOrFail();
    $stage = OpportunityStage::query()->forceCreate([
        'parent_id' => null,
        'presettable_id' => $presettable->id,
        'entity_id' => $entity->id,
    ]);
    $party = Party::query()->create([
        'company_id' => $company->id,
        'name' => 'Customer',
        'is_customer' => true,
        'is_supplier' => false,
    ]);
    Opportunity::query()->create([
        'counterparty_id' => $party->counterparty_id,
        'stage_taxonomy_id' => $stage->id,
        'name' => 'Deal A',
        'status' => OpportunityStatus::Open,
        'expected_value_doc' => '1000.0000',
        'expected_currency_doc' => 'EUR',
        'expected_value_local' => '1000.0000',
        'expected_currency_local' => 'EUR',
        'expected_fx_rate' => '1.0000',
        'won_at' => now(),
    ]);
    Opportunity::query()->create([
        'counterparty_id' => $party->counterparty_id,
        'stage_taxonomy_id' => $stage->id,
        'name' => 'Deal B',
        'status' => OpportunityStatus::Lost,
        'expected_value_doc' => '500.0000',
        'expected_currency_doc' => 'EUR',
        'expected_value_local' => '500.0000',
        'expected_currency_local' => 'EUR',
        'expected_fx_rate' => '1.0000',
        'lost_at' => now(),
    ]);

    $result = app(SalesPipelineService::class)->generate();

    expect($result['total_count'])->toBe(2)
        ->and($result['won_count'])->toBe(1)
        ->and($result['lost_count'])->toBe(1)
        ->and($result['by_status']['open']['count'])->toBe(1)
        ->and($result['by_status']['lost']['count'])->toBe(1)
        ->and($result['total_expected_value_doc'])->toBe('1500.0000');
});
