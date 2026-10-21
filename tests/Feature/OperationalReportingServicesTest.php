<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Filament\Pages\StockValuationPage;
use Modules\ERP\Models\Company;
use Modules\ERP\Models\Item;
use Modules\ERP\Models\StockLevel;
use Modules\ERP\Models\Warehouse;
use Modules\ERP\Services\Reporting\OperationalReportCsvExporter;
use Modules\ERP\Services\Reporting\StockValuationService;

uses(RefreshDatabase::class);

it('summarizes stock valuation from stock levels', function (): void {
    $company = Company::query()->create([
        'slug' => 'stock-valuation',
        'name' => 'Stock Valuation Company',
        'fiscal_country' => 'IT',
        'default_currency' => 'EUR',
        'is_default' => false,
    ]);
    $other_company = Company::query()->create([
        'slug' => 'other-stock-valuation',
        'name' => 'Other Stock Valuation Company',
        'fiscal_country' => 'IT',
        'default_currency' => 'EUR',
        'is_default' => false,
    ]);
    $warehouse = Warehouse::query()->create([
        'company_id' => $company->id,
        'name' => 'Main warehouse',
        'code' => 'MAIN',
    ]);
    $other_warehouse = Warehouse::query()->create([
        'company_id' => $other_company->id,
        'name' => 'Other warehouse',
        'code' => 'OTHER',
    ]);
    $item = Item::query()->create([
        'company_id' => $company->id,
        'name' => 'Tracked item',
        'sku' => 'SKU-1',
        'uom' => 'pcs',
        'costing_method' => 'weighted_avg',
    ]);
    $second_item = Item::query()->create([
        'company_id' => $company->id,
        'name' => 'Second item',
        'sku' => 'SKU-2',
        'uom' => 'pcs',
        'costing_method' => 'fifo',
    ]);
    $other_item = Item::query()->create([
        'company_id' => $other_company->id,
        'name' => 'Other item',
        'sku' => 'SKU-3',
        'uom' => 'pcs',
        'costing_method' => 'weighted_avg',
    ]);

    StockLevel::query()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => '10.5000',
        'weighted_avg_cost' => '3.2500',
    ]);
    StockLevel::query()->create([
        'company_id' => $company->id,
        'item_id' => $second_item->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => '2.0000',
        'weighted_avg_cost' => '10.0000',
    ]);
    StockLevel::query()->create([
        'company_id' => $other_company->id,
        'item_id' => $other_item->id,
        'warehouse_id' => $other_warehouse->id,
        'quantity' => '99.0000',
        'weighted_avg_cost' => '99.0000',
    ]);

    $result = app(StockValuationService::class)->generate((int) $company->id);

    expect($result['total_quantity'])->toBe('12.5000')
        ->and($result['total_value'])->toBe('54.1250')
        ->and($result['rows'])->toHaveCount(2)
        ->and($result['rows'][0]['sku'])->toBe('SKU-1')
        ->and($result['rows'][0]['quantity'])->toBe('10.5000')
        ->and($result['rows'][0]['weighted_avg_cost'])->toBe('3.2500')
        ->and($result['rows'][0]['value'])->toBe('34.1250')
        ->and($result['rows'][1]['sku'])->toBe('SKU-2')
        ->and($result['rows'][1]['value'])->toBe('20.0000');
});

it('filters stock valuation by warehouse', function (): void {
    $company = Company::query()->create([
        'slug' => 'stock-valuation-filter',
        'name' => 'Stock Valuation Filter Company',
        'fiscal_country' => 'IT',
        'default_currency' => 'EUR',
        'is_default' => false,
    ]);
    $main_warehouse = Warehouse::query()->create([
        'company_id' => $company->id,
        'name' => 'Main warehouse',
        'code' => 'MAIN',
    ]);
    $secondary_warehouse = Warehouse::query()->create([
        'company_id' => $company->id,
        'name' => 'Secondary warehouse',
        'code' => 'SEC',
    ]);
    $item = Item::query()->create([
        'company_id' => $company->id,
        'name' => 'Filtered item',
        'sku' => 'SKU-F',
        'uom' => 'pcs',
        'costing_method' => 'weighted_avg',
    ]);

    StockLevel::query()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'warehouse_id' => $main_warehouse->id,
        'quantity' => '4.0000',
        'weighted_avg_cost' => '5.0000',
    ]);
    StockLevel::query()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'warehouse_id' => $secondary_warehouse->id,
        'quantity' => '10.0000',
        'weighted_avg_cost' => '7.0000',
    ]);

    $result = app(StockValuationService::class)->generate((int) $company->id, [
        'warehouse_id' => (int) $main_warehouse->id,
    ]);

    expect($result['rows'])->toHaveCount(1)
        ->and($result['rows'][0]['warehouse_code'])->toBe('MAIN')
        ->and($result['total_quantity'])->toBe('4.0000')
        ->and($result['total_value'])->toBe('20.0000');
});

it('orders stock valuation by item sku and warehouse code while preserving totals', function (): void {
    $company = Company::query()->create([
        'slug' => 'stock-valuation-ordering',
        'name' => 'Stock Valuation Ordering Company',
        'fiscal_country' => 'IT',
        'default_currency' => 'EUR',
        'is_default' => false,
    ]);
    $warehouse_b = Warehouse::query()->create(['company_id' => $company->id, 'name' => 'B Warehouse', 'code' => 'B']);
    $warehouse_a = Warehouse::query()->create(['company_id' => $company->id, 'name' => 'A Warehouse', 'code' => 'A']);
    $item_b = Item::query()->create(['company_id' => $company->id, 'name' => 'B Item', 'sku' => 'B-ITEM', 'uom' => 'pcs', 'costing_method' => 'weighted_avg']);
    $item_a = Item::query()->create(['company_id' => $company->id, 'name' => 'A Item', 'sku' => 'A-ITEM', 'uom' => 'pcs', 'costing_method' => 'weighted_avg']);

    StockLevel::query()->create(['company_id' => $company->id, 'item_id' => $item_b->id, 'warehouse_id' => $warehouse_b->id, 'quantity' => '2.0000', 'weighted_avg_cost' => '5.0000']);
    StockLevel::query()->create(['company_id' => $company->id, 'item_id' => $item_a->id, 'warehouse_id' => $warehouse_a->id, 'quantity' => '3.0000', 'weighted_avg_cost' => '7.0000']);

    $result = app(StockValuationService::class)->generate((int) $company->id);

    expect($result['rows'][0]['sku'])->toBe('A-ITEM')
        ->and($result['total_quantity'])->toBe('5.0000')
        ->and($result['total_value'])->toBe('31.0000');
});

it('exports operational reports as csv', function (): void {
    $exporter = app(OperationalReportCsvExporter::class);

    $stock_csv = $exporter->stockValuation([
        'rows' => [
            [
                'sku' => 'SKU-1',
                'item_name' => 'Item, one',
                'warehouse_code' => 'MAIN',
                'warehouse_name' => 'Main warehouse',
                'quantity' => '2.0000',
                'weighted_avg_cost' => '5.0000',
                'value' => '10.0000',
            ],
        ],
        'total_quantity' => '2.0000',
        'total_value' => '10.0000',
    ]);

    expect($stock_csv)->toContain('SKU,"Item name","Warehouse code","Warehouse name",Quantity,"Weighted avg cost",Value')
        ->and($stock_csv)->toContain('SKU-1,"Item, one",MAIN,"Main warehouse",2.0000,5.0000,10.0000');
});

it('operational report pages expose csv export actions', function (): void {
    expect(method_exists(StockValuationPage::class, 'exportCsv'))->toBeTrue();
});
