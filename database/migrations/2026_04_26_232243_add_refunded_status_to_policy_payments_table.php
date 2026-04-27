<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->dropCheckConstraint('policy_payments', 'chk_pp_combo');
        $this->dropCheckConstraint('policy_payments', 'chk_pp_status');

        DB::statement("
            ALTER TABLE policy_payments
            MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'
        ");

        DB::statement("
            ALTER TABLE policy_payments
            ADD CONSTRAINT chk_pp_status
            CHECK (status IN (
                'draft',
                'scheduled',
                'paid',
                'overdue',
                'canceled',
                'refunded'
            ))
        ");

        DB::statement("
            ALTER TABLE policy_payments
            ADD CONSTRAINT chk_pp_combo
            CHECK (
                (
                    method = 'no_method'
                    AND status IN ('draft', 'overdue', 'canceled')
                )
                OR
                (
                    method IN ('cash', 'card')
                    AND status IN ('paid', 'canceled', 'refunded')
                )
                OR
                (
                    method = 'transfer'
                    AND status IN ('scheduled', 'paid', 'overdue', 'canceled', 'refunded')
                )
            )
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->dropCheckConstraint('policy_payments', 'chk_pp_combo');
        $this->dropCheckConstraint('policy_payments', 'chk_pp_status');

        DB::statement("
            UPDATE policy_payments
            SET status = 'canceled'
            WHERE status = 'refunded'
        ");

        DB::statement("
            ALTER TABLE policy_payments
            MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'
        ");

        DB::statement("
            ALTER TABLE policy_payments
            ADD CONSTRAINT chk_pp_status
            CHECK (status IN (
                'draft',
                'scheduled',
                'paid',
                'overdue',
                'canceled'
            ))
        ");

        DB::statement("
            ALTER TABLE policy_payments
            ADD CONSTRAINT chk_pp_combo
            CHECK (
                (
                    method = 'no_method'
                    AND status IN ('draft', 'overdue', 'canceled')
                )
                OR
                (
                    method IN ('cash', 'card')
                    AND status IN ('paid', 'canceled')
                )
                OR
                (
                    method = 'transfer'
                    AND status IN ('scheduled', 'paid', 'overdue', 'canceled')
                )
            )
        ");
    }

    private function dropCheckConstraint(string $table, string $constraint): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE {$table} DROP CHECK {$constraint}");

            return;
        }

        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}");
    }
};