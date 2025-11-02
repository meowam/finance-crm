<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE policy_payments DROP CONSTRAINT IF EXISTS chk_pp_method");
        DB::statement("ALTER TABLE policy_payments DROP CONSTRAINT IF EXISTS chk_pp_status");
        DB::statement("ALTER TABLE policy_payments DROP CONSTRAINT IF EXISTS chk_pp_combo");

        DB::statement("
            ALTER TABLE policy_payments
            ADD CONSTRAINT chk_pp_method
            CHECK (method IN ('cash','card','transfer','no_method'))
        ");

        DB::statement("
            ALTER TABLE policy_payments
            ADD CONSTRAINT chk_pp_status
            CHECK (status IN ('draft','scheduled','paid','overdue','canceled'))
        ");

        DB::statement("
            ALTER TABLE policy_payments
            ADD CONSTRAINT chk_pp_combo
            CHECK (
                (method IN ('cash','card') AND status IN ('paid','canceled')) OR
                (method = 'transfer' AND status IN ('scheduled','paid','canceled','overdue')) OR
                (method = 'no_method' AND status IN ('draft','overdue','canceled'))
            )
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE policy_payments DROP CONSTRAINT chk_pp_combo");
        DB::statement("ALTER TABLE policy_payments DROP CONSTRAINT chk_pp_status");
        DB::statement("ALTER TABLE policy_payments DROP CONSTRAINT chk_pp_method");
    }
};
