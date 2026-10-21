<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Core\Tests\Support\HttpContext;
use Modules\CRM\Models\Counterparty;
use Modules\ERP\Filament\Resources\PaymentRequests\Pages\ListPaymentRequests;
use Modules\ERP\Filament\Resources\Payments\Pages\ListPayments;
use Modules\ERP\Filament\Resources\Projects\Pages\ListProjects;
use Modules\ERP\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use Modules\ERP\Filament\Resources\Quotations\Pages\ListQuotations;
use Modules\ERP\Filament\Resources\ReturnOrders\Pages\ListReturnOrders;
use Modules\ERP\Filament\Resources\SalesOrders\Pages\ListSalesOrders;
use Modules\ERP\Filament\Resources\SupplierReturns\Pages\ListSupplierReturns;
use Modules\ERP\Models\Party;
use Modules\ERP\Models\Quotation;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

dataset('party list pages', [
    'quotations' => [ListQuotations::class],
    'sales orders' => [ListSalesOrders::class],
    'purchase orders' => [ListPurchaseOrders::class],
    'projects' => [ListProjects::class],
    'payments' => [ListPayments::class],
    'return orders' => [ListReturnOrders::class],
    'supplier returns' => [ListSupplierReturns::class],
    'payment requests' => [ListPaymentRequests::class],
]);

it('sorts and searches the party column of every document list by the counterparty name', function (string $page): void {
    HttpContext::panelActorWithoutApproval(new ($page::getResource()::getModel()), ['select']);

    Livewire::test($page)
        ->loadTable()
        ->sortTable('party.counterparty.name')
        ->searchTable('alpha')
        ->assertSuccessful();
})->with('party list pages');

it('finds a quotation by the name of its party', function (): void {
    HttpContext::panelActorWithoutApproval(new Quotation, ['select']);

    $alpha = Party::factory()->create(['counterparty_id' => Counterparty::factory()->create(['name' => 'Alpha Industries'])]);
    $beta = Party::factory()->create(['counterparty_id' => Counterparty::factory()->create(['name' => 'Beta Trading'])]);
    $quotation_alpha = Quotation::query()->create(['company_id' => $alpha->company_id, 'party_id' => $alpha->getKey(), 'currency' => 'EUR', 'status' => 'draft', 'version' => 0]);
    $quotation_beta = Quotation::query()->create(['company_id' => $beta->company_id, 'party_id' => $beta->getKey(), 'currency' => 'EUR', 'status' => 'draft', 'version' => 0]);

    Livewire::test(ListQuotations::class)
        ->loadTable()
        ->searchTable('alpha')
        ->assertCanSeeTableRecords([$quotation_alpha])
        ->assertCanNotSeeTableRecords([$quotation_beta]);
});
