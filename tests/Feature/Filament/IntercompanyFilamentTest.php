<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Modules\Core\Models\Role;
use Modules\Core\Tests\Support\HttpContext;
use Modules\ERP\Filament\Resources\Companies\Pages\EditCompany;
use Modules\ERP\Filament\Resources\Parties\Pages\ListParties;
use Modules\ERP\Models\Company;
use Modules\ERP\Models\Party;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function signInAsErpSuperadmin(): void
{
    $admin = User::factory()->create(['email_verified_at' => now(), 'password' => 'Aa1!FilamentAdminPass']);
    $admin->roles()->attach(Role::factory()->create(['name' => 'superadmin', 'guard_name' => 'web']));
    test()->actingAs($admin, 'admin');
}

it('gives a company its counterparty from the edit page', function (): void {
    signInAsErpSuperadmin();
    $company = Company::factory()->create();

    Livewire::test(EditCompany::class, ['record' => $company->getKey()])
        ->callAction('linkCounterparty')
        ->assertHasNoActionErrors();

    expect($company->fresh()->counterparty_id)->not->toBeNull();
});

it('hides the link action once the company has a counterparty', function (): void {
    signInAsErpSuperadmin();
    $company = Company::factory()->create();
    $company->ensureCounterparty();

    Livewire::test(EditCompany::class, ['record' => $company->getKey()])
        ->assertActionHidden('linkCounterparty');
});

it('filters the parties down to the intercompany ones', function (): void {
    HttpContext::panelActorWithoutApproval(new Party, ['select']);
    $alfa = Company::factory()->create();
    $beta = Company::factory()->create();
    $intercompany = Party::factory()->create(['company_id' => $alfa->getKey(), 'counterparty_id' => $beta->ensureCounterparty()->getKey()]);
    $ordinary = Party::factory()->create(['company_id' => $alfa->getKey()]);

    Livewire::test(ListParties::class)
        ->loadTable()
        ->filterTable('intercompany')
        ->assertCanSeeTableRecords([$intercompany])
        ->assertCanNotSeeTableRecords([$ordinary]);
});
