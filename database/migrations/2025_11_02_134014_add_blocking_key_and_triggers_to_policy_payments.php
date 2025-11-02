<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE policy_payments ADD COLUMN blocking_policy_id BIGINT GENERATED ALWAYS AS (CASE WHEN status IN ('paid','scheduled') THEN policy_id ELSE NULL END) STORED");
        DB::statement("CREATE UNIQUE INDEX ux_policy_payments_blocking ON policy_payments (blocking_policy_id)");
        DB::unprepared("
            CREATE TRIGGER trg_policy_payments_prevent_update_overdue
            BEFORE UPDATE ON policy_payments
            FOR EACH ROW
            BEGIN
                IF OLD.status = 'overdue' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Overdue payments are immutable';
                END IF;
            END
        ");
        DB::unprepared("
            CREATE TRIGGER trg_policy_payments_prevent_delete_overdue
            BEFORE DELETE ON policy_payments
            FOR EACH ROW
            BEGIN
                IF OLD.status = 'overdue' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Overdue payments cannot be deleted';
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS trg_policy_payments_prevent_update_overdue");
        DB::statement("DROP TRIGGER IF EXISTS trg_policy_payments_prevent_delete_overdue");
        DB::statement("DROP INDEX ux_policy_payments_blocking ON policy_payments");
        DB::statement("ALTER TABLE policy_payments DROP COLUMN blocking_policy_id");
    }
};
