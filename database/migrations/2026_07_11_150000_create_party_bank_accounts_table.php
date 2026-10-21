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
    public function up(): void
    {
        $party_bank_accounts_table = ERPTables::PartyBankAccounts->value;

        Schema::create($party_bank_accounts_table, function (Blueprint $table) use ($party_bank_accounts_table): void {
            $table->id();
            ERPMigrateUtils::companyForeign($table);
            $table->foreignId('party_id')
                ->constrained(ERPTables::Parties->value, 'id', "{$party_bank_accounts_table}_party_id_FK")
                ->cascadeOnDelete();
            MigrateUtils::prefixIndex($table, 'party_id');
            $table->foreignId('counterparty_bank_account_id')
                ->constrained(CRMTables::CounterpartyBankAccounts->value, 'id', "{$party_bank_accounts_table}_counterparty_bank_account_id_FK")
                ->restrictOnDelete()
                ->comment('The bank account of the counterparty this company uses with the party');
            $table->string('direct_debit_mandate_reference', 35)->nullable();
            $table->date('direct_debit_mandate_signed_on')->nullable();
            $table->string('direct_debit_mandate_scheme', 16)->nullable();
            $table->boolean('is_default')->default(false);
            $table->index(['company_id', 'party_id'], "{$party_bank_accounts_table}_company_party_idx");
            $table->unique(['company_id', 'party_id', 'counterparty_bank_account_id'], "{$party_bank_accounts_table}_party_account_UN");

            MigrateUtils::timestamps($table, hasCreateUpdate: true, hasSoftDelete: true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ERPTables::PartyBankAccounts->value);
    }
};
