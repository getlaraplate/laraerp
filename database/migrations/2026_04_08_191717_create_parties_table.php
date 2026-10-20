<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Helpers\MigrateUtils;
use Modules\CRM\Enums\CRMTables;
use Modules\ERP\Enums\ERPTables;
use Modules\ERP\Helpers\ERPMigrateUtils;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $parties_table = ERPTables::Parties->value;
        Schema::create($parties_table, function (Blueprint $table) use ($parties_table): void {
            $table->id();
            ERPMigrateUtils::companyForeign($table);
            $table->boolean('is_customer')->default(true)->comment('Whether the party is a customer');
            $table->boolean('is_supplier')->default(false)->comment('Whether the party is a supplier');
            $table->boolean('is_active')->default(true)->index("{$parties_table}_is_active_IDX")->comment('Whether the party is active');
            $table->foreignId('counterparty_id')
                ->constrained(CRMTables::Counterparties->value, 'id', "{$parties_table}_counterparty_id_FK")
                ->restrictOnDelete()
                ->comment('The shared subject this party is the relationship of, with one company');
            $table->unique(['counterparty_id', 'company_id'], "{$parties_table}_counterparty_company_UN");

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ERPTables::Parties->value);
    }
};
