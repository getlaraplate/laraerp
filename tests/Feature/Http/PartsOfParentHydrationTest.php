<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Core\Support\CrudApiExposure;
use Modules\Core\Support\PermissionName;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\InvoiceLine;

uses(RefreshDatabase::class);

/*
 * The lines of a document are parts of it: loaded from the document they inherit its visibility, so a token
 * carrying only the document's ability reads them, whatever its owner's roles say of the lines' own permission.
 */

it('loads the lines of an invoice for a token carrying only the invoice ability', function (): void {
    Cache::flush();
    CrudApiExposure::enable();
    $role = Role::factory()->create(['name' => 'erp_partsparent_' . uniqid(), 'guard_name' => 'api']);

    foreach ([Invoice::class, InvoiceLine::class] as $model_class) {
        foreach (['web', 'api'] as $guard) {
            Permission::findOrCreate(PermissionName::forClass($model_class, 'select'), $guard);
        }

        $role->givePermissionTo(Permission::findOrCreate(PermissionName::forClass($model_class, 'select'), 'api'));
    }

    // Created after the permissions: the existence of a permission is memoized for the request, and the test
    // shares one with the factory, so a lookup made while creating the invoice must already see them.
    $invoice = Invoice::factory()->withLines(2)->create();

    // The class the auth provider resolves: a token stored for it authenticates.
    $user = App\Models\User::query()->findOrFail(User::factory()->create()->getKey());
    $user->assignRole($role);
    $token = $user->fresh()->createToken('erp-partsparent', [PermissionName::forClass(Invoice::class, 'select')])->plainTextToken;

    $response = $this->getJson(
        '/api/v1/detail/erp/invoices?' . http_build_query(['id' => $invoice->id, 'relations' => ['lines']]),
        ['Authorization' => 'Bearer ' . $token],
    );

    $response->assertOk();
    expect($response->json('data.lines'))->toHaveCount(2);
});
