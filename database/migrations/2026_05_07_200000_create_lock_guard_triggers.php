<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Modules\Core\Support\Db2Drivers;
use Modules\ERP\Enums\ERPTables;

/**
 * Adds database-level safety nets for immutable ERP document chains.
 *
 * MySQL, MariaDB, PostgreSQL and Db2 for i enforce them with triggers: BEFORE guards that block a
 * frozen record, and AFTER chain triggers that freeze the linked documents. SQLite and Oracle use the
 * equivalent Eloquent guards because portable trigger DDL is not available through Laravel's schema builder.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const array LOCKABLE_TABLES = [
        ERPTables::Quotations->value,
        ERPTables::SalesOrders->value,
        ERPTables::Projects->value,
    ];

    /**
     * @var list<string>
     */
    private const array COMMERCIAL_LINE_COLUMNS = ['sales_order_id', 'quotation_item_id', 'item_id', 'name', 'qty_ordered', 'unit_price'];

    private ?Connection $activeConnection = null;

    public function up(): void
    {
        $driver = $this->connection()->getDriverName();

        match (true) {
            in_array($driver, ['mysql', 'mariadb'], true) => $this->installMysqlTriggers(),
            $driver === 'pgsql' => $this->installPostgresTriggers(),
            Db2Drivers::is($driver) => $this->installDb2Triggers(),
            default => null,
        };
    }

    public function down(): void
    {
        $driver = $this->connection()->getDriverName();

        match (true) {
            in_array($driver, ['mysql', 'mariadb'], true) => $this->dropMysqlTriggers(),
            $driver === 'pgsql' => $this->dropPostgresTriggers(),
            Db2Drivers::is($driver) => $this->dropDb2Triggers(),
            default => null,
        };
    }

    /**
     * Matches a row that is locked **for everyone**: a freeze.
     *
     * A trigger cannot know who is making the write, so it must not block on the mere presence of a
     * lock. A lock that carries a `locked_user_id` is a lease, and its owner is entitled to edit the
     * record, release it and move its deadline; only an ownerless lock is an immutability guarantee,
     * which is exactly what the chain triggers below create when a document is confirmed.
     *
     * Expiry is checked here too. Lazy expiry frees a lock the moment it lapses everywhere else in
     * the system, but a trigger has no way to benefit from that, so it would keep rejecting writes
     * against a freeze that is already over until the housekeeping sweep caught up.
     */
    private function frozenPredicate(): string
    {
        return 'OLD.locked_at IS NOT NULL'
            . ' AND OLD.locked_user_id IS NULL'
            . ' AND (OLD.locked_until IS NULL OR OLD.locked_until > CURRENT_TIMESTAMP)';
    }

    private function installMysqlTriggers(): void
    {
        foreach (self::LOCKABLE_TABLES as $table) {
            $wrapped_table = $this->table($table);
            $update_trigger = $this->identifier($table . '_lock_guard_update');
            $delete_trigger = $this->identifier($table . '_lock_guard_delete');
            $frozen = $this->frozenPredicate();
            $this->connection()->unprepared("CREATE TRIGGER {$update_trigger}
                BEFORE UPDATE ON {$wrapped_table} FOR EACH ROW
                BEGIN
                    IF {$frozen} AND NEW.locked_at IS NOT NULL THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot modify a frozen record.';
                    END IF;
                END");
            $this->connection()->unprepared("CREATE TRIGGER {$delete_trigger}
                BEFORE DELETE ON {$wrapped_table} FOR EACH ROW
                BEGIN
                    IF {$frozen} THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot delete a frozen record.';
                    END IF;
                END");
        }

        $this->connection()->unprepared($this->mysqlSalesOrderChainTrigger('insert'));
        $this->connection()->unprepared($this->mysqlSalesOrderChainTrigger('update'));
        $this->connection()->unprepared($this->mysqlDeliveryLineChainTrigger('insert'));
        $this->connection()->unprepared($this->mysqlDeliveryLineChainTrigger('update'));

        $lines = $this->table(ERPTables::SalesOrderLines->value);
        $commercial_trigger = $this->identifier(ERPTables::SalesOrderLines->value . '_commercial_lock_guard_update');
        $delete_trigger = $this->identifier(ERPTables::SalesOrderLines->value . '_lock_guard_delete');
        $frozen = $this->frozenPredicate();
        $this->connection()->unprepared("CREATE TRIGGER {$commercial_trigger}
            BEFORE UPDATE ON {$lines} FOR EACH ROW
            BEGIN
                IF {$frozen} AND (
                    NOT (NEW.sales_order_id <=> OLD.sales_order_id)
                    OR NOT (NEW.quotation_item_id <=> OLD.quotation_item_id)
                    OR NOT (NEW.item_id <=> OLD.item_id)
                    OR NOT (NEW.name <=> OLD.name)
                    OR NOT (NEW.qty_ordered <=> OLD.qty_ordered)
                    OR NOT (NEW.unit_price <=> OLD.unit_price)
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot modify commercial fields on a frozen sales order line.';
                END IF;
            END");
        $this->connection()->unprepared("CREATE TRIGGER {$delete_trigger}
            BEFORE DELETE ON {$lines} FOR EACH ROW
            BEGIN
                IF {$frozen} THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot delete a frozen sales order line.';
                END IF;
            END");
    }

    private function installPostgresTriggers(): void
    {
        $frozen = $this->frozenPredicate();
        $record_guard = $this->identifier('erp_locked_record_guard');
        $this->connection()->unprepared("CREATE OR REPLACE FUNCTION {$record_guard}() RETURNS trigger AS $$
            BEGIN
                IF {$frozen} AND (TG_OP = 'DELETE' OR NEW.locked_at IS NOT NULL) THEN
                    RAISE EXCEPTION 'Cannot modify or delete a frozen record.';
                END IF;
                RETURN CASE WHEN TG_OP = 'DELETE' THEN OLD ELSE NEW END;
            END;
        $$ LANGUAGE plpgsql");

        foreach (self::LOCKABLE_TABLES as $table) {
            $wrapped_table = $this->table($table);
            $update_trigger = $this->identifier($table . '_lock_guard_update');
            $delete_trigger = $this->identifier($table . '_lock_guard_delete');
            $this->connection()->unprepared("CREATE TRIGGER {$update_trigger} BEFORE UPDATE ON {$wrapped_table}
                FOR EACH ROW EXECUTE FUNCTION {$record_guard}()");
            $this->connection()->unprepared("CREATE TRIGGER {$delete_trigger} BEFORE DELETE ON {$wrapped_table}
                FOR EACH ROW EXECUTE FUNCTION {$record_guard}()");
        }

        $quotations = $this->table(ERPTables::Quotations->value);
        $projects = $this->table(ERPTables::Projects->value);
        $order_chain = $this->identifier('erp_lock_sales_order_chain');
        $this->connection()->unprepared("CREATE OR REPLACE FUNCTION {$order_chain}() RETURNS trigger AS $$
            BEGIN
                IF NEW.status IN ('confirmed', 'partially_evased', 'fully_evased') THEN
                    UPDATE {$quotations} SET locked_at = COALESCE(locked_at, CURRENT_TIMESTAMP)
                        WHERE id = NEW.quotation_id AND locked_at IS NULL;
                    UPDATE {$projects} SET locked_at = COALESCE(locked_at, CURRENT_TIMESTAMP)
                        WHERE id = NEW.project_id AND locked_at IS NULL;
                END IF;
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql");

        $orders = $this->table(ERPTables::SalesOrders->value);
        $order_insert_trigger = $this->identifier(ERPTables::SalesOrders->value . '_lock_chain_insert');
        $order_update_trigger = $this->identifier(ERPTables::SalesOrders->value . '_lock_chain_update');
        $this->connection()->unprepared("CREATE TRIGGER {$order_insert_trigger} AFTER INSERT ON {$orders}
            FOR EACH ROW EXECUTE FUNCTION {$order_chain}()");
        $this->connection()->unprepared("CREATE TRIGGER {$order_update_trigger} AFTER UPDATE ON {$orders}
            FOR EACH ROW EXECUTE FUNCTION {$order_chain}()");

        $delivery_lines = $this->table(ERPTables::DeliveryNoteLines->value);
        $order_lines = $this->table(ERPTables::SalesOrderLines->value);
        $line_chain = $this->identifier('erp_lock_sales_order_line_chain');
        $this->connection()->unprepared("CREATE OR REPLACE FUNCTION {$line_chain}() RETURNS trigger AS $$
            BEGIN
                IF NEW.sales_order_line_id IS NOT NULL THEN
                    UPDATE {$order_lines} SET locked_at = COALESCE(locked_at, CURRENT_TIMESTAMP)
                        WHERE id = NEW.sales_order_line_id AND locked_at IS NULL;
                END IF;
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql");
        $delivery_insert_trigger = $this->identifier(ERPTables::DeliveryNoteLines->value . '_lock_chain_insert');
        $delivery_update_trigger = $this->identifier(ERPTables::DeliveryNoteLines->value . '_lock_chain_update');
        $this->connection()->unprepared("CREATE TRIGGER {$delivery_insert_trigger} AFTER INSERT ON {$delivery_lines}
            FOR EACH ROW EXECUTE FUNCTION {$line_chain}()");
        $this->connection()->unprepared("CREATE TRIGGER {$delivery_update_trigger} AFTER UPDATE ON {$delivery_lines}
            FOR EACH ROW EXECUTE FUNCTION {$line_chain}()");

        $line_guard = $this->identifier('erp_sales_order_line_guard');
        $this->connection()->unprepared("CREATE OR REPLACE FUNCTION {$line_guard}() RETURNS trigger AS $$
            BEGIN
                IF TG_OP = 'DELETE' AND {$frozen} THEN
                    RAISE EXCEPTION 'Cannot delete a frozen sales order line.';
                END IF;
                IF TG_OP = 'UPDATE' AND {$frozen} AND (
                    NEW.sales_order_id IS DISTINCT FROM OLD.sales_order_id
                    OR NEW.quotation_item_id IS DISTINCT FROM OLD.quotation_item_id
                    OR NEW.item_id IS DISTINCT FROM OLD.item_id
                    OR NEW.name IS DISTINCT FROM OLD.name
                    OR NEW.qty_ordered IS DISTINCT FROM OLD.qty_ordered
                    OR NEW.unit_price IS DISTINCT FROM OLD.unit_price
                ) THEN
                    RAISE EXCEPTION 'Cannot modify commercial fields on a frozen sales order line.';
                END IF;
                RETURN CASE WHEN TG_OP = 'DELETE' THEN OLD ELSE NEW END;
            END;
        $$ LANGUAGE plpgsql");
        $commercial_trigger = $this->identifier(ERPTables::SalesOrderLines->value . '_commercial_lock_guard_update');
        $delete_trigger = $this->identifier(ERPTables::SalesOrderLines->value . '_lock_guard_delete');
        $this->connection()->unprepared("CREATE TRIGGER {$commercial_trigger} BEFORE UPDATE ON {$order_lines}
            FOR EACH ROW EXECUTE FUNCTION {$line_guard}()");
        $this->connection()->unprepared("CREATE TRIGGER {$delete_trigger} BEFORE DELETE ON {$order_lines}
            FOR EACH ROW EXECUTE FUNCTION {$line_guard}()");
    }

    private function dropMysqlTriggers(): void
    {
        foreach (self::LOCKABLE_TABLES as $table) {
            $this->connection()->unprepared('DROP TRIGGER IF EXISTS ' . $this->identifier($table . '_lock_guard_update'));
            $this->connection()->unprepared('DROP TRIGGER IF EXISTS ' . $this->identifier($table . '_lock_guard_delete'));
        }

        foreach ($this->chainTriggerNames() as $trigger) {
            $this->connection()->unprepared('DROP TRIGGER IF EXISTS ' . $this->identifier($trigger));
        }
    }

    private function dropPostgresTriggers(): void
    {
        foreach (self::LOCKABLE_TABLES as $table) {
            $wrapped_table = $this->table($table);
            $update_trigger = $this->identifier($table . '_lock_guard_update');
            $delete_trigger = $this->identifier($table . '_lock_guard_delete');
            $this->connection()->unprepared("DROP TRIGGER IF EXISTS {$update_trigger} ON {$wrapped_table}");
            $this->connection()->unprepared("DROP TRIGGER IF EXISTS {$delete_trigger} ON {$wrapped_table}");
        }

        $tables = [
            ERPTables::SalesOrders->value,
            ERPTables::SalesOrders->value,
            ERPTables::DeliveryNoteLines->value,
            ERPTables::DeliveryNoteLines->value,
            ERPTables::SalesOrderLines->value,
            ERPTables::SalesOrderLines->value,
        ];

        foreach (array_combine($this->chainTriggerNames(), $tables) as $trigger => $table) {
            $this->connection()->unprepared(sprintf(
                'DROP TRIGGER IF EXISTS %s ON %s',
                $this->identifier($trigger),
                $this->table($table),
            ));
        }

        $this->connection()->unprepared('DROP FUNCTION IF EXISTS ' . $this->identifier('erp_locked_record_guard') . '()');
        $this->connection()->unprepared('DROP FUNCTION IF EXISTS ' . $this->identifier('erp_lock_sales_order_chain') . '()');
        $this->connection()->unprepared('DROP FUNCTION IF EXISTS ' . $this->identifier('erp_lock_sales_order_line_chain') . '()');
        $this->connection()->unprepared('DROP FUNCTION IF EXISTS ' . $this->identifier('erp_sales_order_line_guard') . '()');
    }

    /**
     * @return list<string>
     */
    private function chainTriggerNames(): array
    {
        return [
            ERPTables::SalesOrders->value . '_lock_chain_insert',
            ERPTables::SalesOrders->value . '_lock_chain_update',
            ERPTables::DeliveryNoteLines->value . '_lock_chain_insert',
            ERPTables::DeliveryNoteLines->value . '_lock_chain_update',
            ERPTables::SalesOrderLines->value . '_commercial_lock_guard_update',
            ERPTables::SalesOrderLines->value . '_lock_guard_delete',
        ];
    }

    private function mysqlSalesOrderChainTrigger(string $operation): string
    {
        $orders = $this->table(ERPTables::SalesOrders->value);
        $quotations = $this->table(ERPTables::Quotations->value);
        $projects = $this->table(ERPTables::Projects->value);
        $trigger = $this->identifier(ERPTables::SalesOrders->value . '_lock_chain_' . $operation);

        return "CREATE TRIGGER {$trigger} AFTER {$operation} ON {$orders} FOR EACH ROW
            BEGIN
                IF NEW.status IN ('confirmed', 'partially_evased', 'fully_evased') THEN
                    UPDATE {$quotations} SET locked_at = COALESCE(locked_at, CURRENT_TIMESTAMP)
                        WHERE id = NEW.quotation_id AND locked_at IS NULL;
                    UPDATE {$projects} SET locked_at = COALESCE(locked_at, CURRENT_TIMESTAMP)
                        WHERE id = NEW.project_id AND locked_at IS NULL;
                END IF;
            END";
    }

    private function mysqlDeliveryLineChainTrigger(string $operation): string
    {
        $delivery_lines = $this->table(ERPTables::DeliveryNoteLines->value);
        $order_lines = $this->table(ERPTables::SalesOrderLines->value);
        $trigger = $this->identifier(ERPTables::DeliveryNoteLines->value . '_lock_chain_' . $operation);

        return "CREATE TRIGGER {$trigger} AFTER {$operation} ON {$delivery_lines} FOR EACH ROW
            BEGIN
                IF NEW.sales_order_line_id IS NOT NULL THEN
                    UPDATE {$order_lines} SET locked_at = COALESCE(locked_at, CURRENT_TIMESTAMP)
                        WHERE id = NEW.sales_order_line_id AND locked_at IS NULL;
                END IF;
            END";
    }

    /**
     * Db2 for i takes the same guards as PostgreSQL and MySQL: BEFORE triggers that signal on a frozen record.
     *
     * The SQLSTATE is '75001', not MySQL's '45000': a user-defined SQLSTATE on Db2 starts with 7, 8, 9 or I-Z.
     */
    private function installDb2Triggers(): void
    {
        $frozen = $this->db2FrozenPredicate();

        foreach (self::LOCKABLE_TABLES as $table) {
            $this->createDb2Trigger($table . '_lock_guard_update', 'UPDATE', $table, "{$frozen} AND N." . $this->identifier('locked_at') . ' IS NOT NULL', 'Cannot modify a frozen record.');
            $this->createDb2Trigger($table . '_lock_guard_delete', 'DELETE', $table, $frozen, 'Cannot delete a frozen record.');
        }

        $lines = ERPTables::SalesOrderLines->value;
        $changed = implode(' OR ', array_map(
            fn (string $column): string => $this->db2Distinct($column),
            self::COMMERCIAL_LINE_COLUMNS,
        ));

        $this->createDb2Trigger($lines . '_commercial_lock_guard_update', 'UPDATE', $lines, "{$frozen} AND ({$changed})", 'Cannot modify commercial fields on a frozen sales order line.');
        $this->createDb2Trigger($lines . '_lock_guard_delete', 'DELETE', $lines, $frozen, 'Cannot delete a frozen sales order line.');

        $this->installDb2ChainTriggers();
    }

    /**
     * The AFTER triggers that freeze the chain: a confirmed sales order freezes its quotation and project,
     * and a delivery note line freezes its sales order line. An existing `locked_at` is kept, as on PostgreSQL.
     */
    private function installDb2ChainTriggers(): void
    {
        $quotations = $this->table(ERPTables::Quotations->value);
        $projects = $this->table(ERPTables::Projects->value);
        $order_lines = $this->table(ERPTables::SalesOrderLines->value);
        $status = $this->identifier('status');

        $order_body = $this->db2FreezeStatement($quotations, 'quotation_id') . ' ' . $this->db2FreezeStatement($projects, 'project_id');
        $order_condition = "N.{$status} IN ('confirmed', 'partially_evased', 'fully_evased')";
        $line_body = $this->db2FreezeStatement($order_lines, 'sales_order_line_id');
        $line_condition = 'N.' . $this->identifier('sales_order_line_id') . ' IS NOT NULL';

        foreach (['INSERT', 'UPDATE'] as $event) {
            $this->createDb2ChainTrigger(ERPTables::SalesOrders->value . '_lock_chain_' . mb_strtolower($event), $event, ERPTables::SalesOrders->value, $order_condition, $order_body);
            $this->createDb2ChainTrigger(ERPTables::DeliveryNoteLines->value . '_lock_chain_' . mb_strtolower($event), $event, ERPTables::DeliveryNoteLines->value, $line_condition, $line_body);
        }
    }

    private function createDb2ChainTrigger(string $name, string $event, string $table, string $condition, string $body): void
    {
        $referencing = $event === 'INSERT' ? 'NEW AS N' : 'OLD AS O NEW AS N';

        $this->connection()->unprepared(sprintf(
            'CREATE OR REPLACE TRIGGER %s AFTER %s ON %s REFERENCING %s FOR EACH ROW WHEN (%s) BEGIN ATOMIC %s END',
            $this->identifier($name),
            $event,
            $this->table($table),
            $referencing,
            $condition,
            $body,
        ));
    }

    private function db2FreezeStatement(string $wrapped_table, string $link_column): string
    {
        $id = $this->identifier('id');
        $locked_at = $this->identifier('locked_at');

        return sprintf(
            'UPDATE %s SET %s = COALESCE(%s, CURRENT_TIMESTAMP) WHERE %s = N.%s AND %s IS NULL;',
            $wrapped_table,
            $locked_at,
            $locked_at,
            $id,
            $this->identifier($link_column),
            $locked_at,
        );
    }

    private function dropDb2Triggers(): void
    {
        foreach (self::LOCKABLE_TABLES as $table) {
            $this->connection()->unprepared('DROP TRIGGER ' . $this->identifier($table . '_lock_guard_update'));
            $this->connection()->unprepared('DROP TRIGGER ' . $this->identifier($table . '_lock_guard_delete'));
        }

        foreach ($this->chainTriggerNames() as $trigger) {
            $this->connection()->unprepared('DROP TRIGGER ' . $this->identifier($trigger));
        }
    }

    private function createDb2Trigger(string $name, string $event, string $table, string $condition, string $message): void
    {
        $referencing = $event === 'DELETE' ? 'OLD AS O' : 'OLD AS O NEW AS N';

        $this->connection()->unprepared(sprintf(
            "CREATE OR REPLACE TRIGGER %s NO CASCADE BEFORE %s ON %s REFERENCING %s FOR EACH ROW WHEN (%s) SIGNAL SQLSTATE '75001' SET MESSAGE_TEXT = '%s'",
            $this->identifier($name),
            $event,
            $this->table($table),
            $referencing,
            $condition,
            $message,
        ));
    }

    private function db2FrozenPredicate(): string
    {
        $locked_at = $this->identifier('locked_at');
        $locked_user_id = $this->identifier('locked_user_id');
        $locked_until = $this->identifier('locked_until');

        return "O.{$locked_at} IS NOT NULL AND O.{$locked_user_id} IS NULL AND (O.{$locked_until} IS NULL OR O.{$locked_until} > CURRENT_TIMESTAMP)";
    }

    /**
     * Null-safe inequality, spelled out because Db2 for i has no portable `IS DISTINCT FROM`.
     */
    private function db2Distinct(string $column): string
    {
        $wrapped = $this->identifier($column);

        return "(N.{$wrapped} <> O.{$wrapped} OR (N.{$wrapped} IS NULL AND O.{$wrapped} IS NOT NULL) OR (N.{$wrapped} IS NOT NULL AND O.{$wrapped} IS NULL))";
    }

    private function connection(): Connection
    {
        return $this->activeConnection ??= app('db')->connection();
    }

    private function table(string $table): string
    {
        return $this->connection()->getQueryGrammar()->wrapTable($table);
    }

    private function identifier(string $identifier): string
    {
        return $this->connection()->getQueryGrammar()->wrap($identifier);
    }
};
