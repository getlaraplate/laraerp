<?php

declare(strict_types=1);

use Modules\Core\Tests\Stubs\Database\Db2PretendConnection;
use Modules\ERP\Helpers\ERPMigrateUtils;

/**
 * @return list<string>
 */
function erp_db2_lock_guard(string $direction): array
{
    return Db2PretendConnection::capture(static function () use ($direction): void {
        $migration = require base_path('Modules/ERP/database/migrations/2026_05_07_200000_create_lock_guard_triggers.php');
        $migration->{$direction}();
    });
}

/**
 * @param  list<string>  $statements
 * @return list<string>
 */
function erp_db2_normalised(array $statements, string $needle): array
{
    return array_values(array_map(
        static fn (string $sql): string => preg_replace('/\s+/', ' ', mb_trim($sql)),
        array_filter($statements, static fn (string $sql): bool => str_contains($sql, $needle)),
    ));
}

it('emits the lock guard triggers that signal on a frozen record on Db2', function (): void {
    $sql = erp_db2_lock_guard('up');
    $joined = implode("\n", $sql);

    foreach (['`', ':NEW', ':OLD', 'RAISE', 'IS DISTINCT FROM', '<=>', "'45000'"] as $foreign) {
        expect($joined)->not->toContain($foreign);
    }

    $update = erp_db2_normalised($sql, 'erp_quotations_lock_guard_update');
    $delete = erp_db2_normalised($sql, 'erp_quotations_lock_guard_delete');

    expect($update)->toHaveCount(1)
        ->and($update[0])->toContain('CREATE OR REPLACE TRIGGER')
        ->and($update[0])->toContain('NO CASCADE BEFORE UPDATE ON')
        ->and($update[0])->toContain('REFERENCING OLD AS O NEW AS N FOR EACH ROW')
        ->and($update[0])->toContain('WHEN (O.locked_at IS NOT NULL AND O.locked_user_id IS NULL AND (O.locked_until IS NULL OR O.locked_until > CURRENT_TIMESTAMP) AND N.locked_at IS NOT NULL)')
        ->and($update[0])->toContain("SIGNAL SQLSTATE '75001' SET MESSAGE_TEXT = 'Cannot modify a frozen record.'")
        ->and($delete)->toHaveCount(1)
        ->and($delete[0])->toContain('NO CASCADE BEFORE DELETE ON')
        ->and($delete[0])->toContain('REFERENCING OLD AS O FOR EACH ROW')
        ->and($delete[0])->toContain('WHEN (O.locked_at IS NOT NULL AND O.locked_user_id IS NULL AND (O.locked_until IS NULL OR O.locked_until > CURRENT_TIMESTAMP))')
        ->and($delete[0])->toContain("SIGNAL SQLSTATE '75001' SET MESSAGE_TEXT = 'Cannot delete a frozen record.'");

    foreach (['erp_sales_orders', 'erp_projects'] as $table) {
        expect(erp_db2_normalised($sql, $table . '_lock_guard_update'))->toHaveCount(1)
            ->and(erp_db2_normalised($sql, $table . '_lock_guard_delete'))->toHaveCount(1);
    }
});

it('guards the commercial fields of a frozen sales order line on Db2', function (): void {
    $sql = erp_db2_lock_guard('up');
    $update = erp_db2_normalised($sql, 'erp_sales_order_lines_commercial_lock_guard_update');
    $delete = erp_db2_normalised($sql, 'erp_sales_order_lines_lock_guard_delete');

    expect($update)->toHaveCount(1)
        ->and($update[0])->toContain('NO CASCADE BEFORE UPDATE ON')
        ->and($update[0])->toContain('(N.sales_order_id <> O.sales_order_id OR (N.sales_order_id IS NULL AND O.sales_order_id IS NOT NULL) OR (N.sales_order_id IS NOT NULL AND O.sales_order_id IS NULL))')
        ->and($update[0])->toContain('N.unit_price <> O.unit_price')
        ->and($update[0])->toContain("SIGNAL SQLSTATE '75001' SET MESSAGE_TEXT = 'Cannot modify commercial fields on a frozen sales order line.'")
        ->and($delete)->toHaveCount(1)
        ->and($delete[0])->toContain('NO CASCADE BEFORE DELETE ON')
        ->and($delete[0])->toContain("SIGNAL SQLSTATE '75001' SET MESSAGE_TEXT = 'Cannot delete a frozen sales order line.'");
});

it('freezes the chain from AFTER triggers on Db2, as PostgreSQL does', function (): void {
    $sql = erp_db2_lock_guard('up');
    $status = "N.status IN ('confirmed', 'partially_evased', 'fully_evased')";

    $insert = erp_db2_normalised($sql, 'erp_sales_orders_lock_chain_insert');
    $update = erp_db2_normalised($sql, 'erp_sales_orders_lock_chain_update');

    expect($insert)->toHaveCount(1)
        ->and($insert[0])->toContain('CREATE OR REPLACE TRIGGER')
        ->and($insert[0])->toContain('AFTER INSERT ON')
        ->and($insert[0])->toContain('REFERENCING NEW AS N FOR EACH ROW')
        ->and($insert[0])->toContain("WHEN ({$status}) BEGIN ATOMIC")
        ->and($insert[0])->toContain('SET locked_at = COALESCE(locked_at, CURRENT_TIMESTAMP) WHERE "ID" = N.quotation_id AND locked_at IS NULL;')
        ->and($insert[0])->toContain('SET locked_at = COALESCE(locked_at, CURRENT_TIMESTAMP) WHERE "ID" = N.project_id AND locked_at IS NULL;')
        ->and($insert[0])->toEndWith('END')
        ->and($update)->toHaveCount(1)
        ->and($update[0])->toContain('AFTER UPDATE ON')
        ->and($update[0])->toContain('REFERENCING OLD AS O NEW AS N FOR EACH ROW')
        ->and($update[0])->toContain("WHEN ({$status}) BEGIN ATOMIC");

    $lines_insert = erp_db2_normalised($sql, 'erp_delivery_note_lines_lock_chain_insert');
    $lines_update = erp_db2_normalised($sql, 'erp_delivery_note_lines_lock_chain_update');

    expect($lines_insert)->toHaveCount(1)
        ->and($lines_insert[0])->toContain('AFTER INSERT ON')
        ->and($lines_insert[0])->toContain('REFERENCING NEW AS N FOR EACH ROW')
        ->and($lines_insert[0])->toContain('WHEN (N.sales_order_line_id IS NOT NULL) BEGIN ATOMIC')
        ->and($lines_insert[0])->toContain('SET locked_at = COALESCE(locked_at, CURRENT_TIMESTAMP) WHERE "ID" = N.sales_order_line_id AND locked_at IS NULL;')
        ->and($lines_update)->toHaveCount(1)
        ->and($lines_update[0])->toContain('AFTER UPDATE ON')
        ->and($lines_update[0])->toContain('REFERENCING OLD AS O NEW AS N FOR EACH ROW');
});

it('drops the lock guard triggers on Db2', function (): void {
    $sql = erp_db2_lock_guard('down');

    expect($sql)->toHaveCount(12)
        ->and(implode("\n", $sql))->not->toContain('IF EXISTS');

    foreach ($sql as $statement) {
        expect($statement)->toStartWith('DROP TRIGGER ');
    }

    expect(implode("\n", $sql))->toContain('erp_sales_orders_lock_chain_insert')
        ->and(implode("\n", $sql))->toContain('erp_sales_orders_lock_chain_update')
        ->and(implode("\n", $sql))->toContain('erp_delivery_note_lines_lock_chain_insert')
        ->and(implode("\n", $sql))->toContain('erp_delivery_note_lines_lock_chain_update')
        ->and(implode("\n", $sql))->toContain('erp_quotations_lock_guard_update')
        ->and(implode("\n", $sql))->toContain('erp_sales_order_lines_commercial_lock_guard_update')
        ->and(implode("\n", $sql))->toContain('erp_sales_order_lines_lock_guard_delete');
});

it('emits its check constraints on Db2', function (): void {
    $sql = Db2PretendConnection::capture(static function (): void {
        ERPMigrateUtils::positiveCheck('erp_items', 'erp_items_qty_CHECK', 'qty');
        ERPMigrateUtils::nonNegativeCheck('erp_items', 'erp_items_price_CHECK', 'price');
        ERPMigrateUtils::nullableNonNegativeCheck('erp_items', 'erp_items_cost_CHECK', 'cost');
    });

    expect($sql)->toHaveCount(3)
        ->and($sql[0])->toContain('ALTER TABLE')->toContain('ADD CONSTRAINT')->toContain('CHECK (')->toContain('> 0')
        ->and($sql[1])->toContain('>= 0')
        ->and($sql[2])->toContain('IS NULL OR')->toContain('>= 0');
});
