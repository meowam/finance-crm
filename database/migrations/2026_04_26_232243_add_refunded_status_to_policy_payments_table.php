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

        DB::statement("
            UPDATE policy_payments
            SET status = 'canceled'
            WHERE status = 'refunded'
        ");

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
        try {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}");
        } catch (Throwable) {
            // Constraint may not exist depending on DB engine/version or previous migration state.
            // Safe to ignore for demo/test migrations.
        }
    }
};