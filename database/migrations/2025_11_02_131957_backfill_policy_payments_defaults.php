<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('policy_payments')->where('status', 'cancelled')->update(['status' => 'canceled']);
        DB::table('policy_payments')->whereNull('method')->update(['method' => 'no_method']);
        DB::table('policy_payments')->where('method', '')->update(['method' => 'no_method']);

        DB::table('policy_payments')
            ->whereNotIn('status', ['draft', 'scheduled', 'paid', 'overdue', 'canceled'])
            ->update(['status' => 'draft']);

        DB::table('policy_payments')
            ->whereIn('method', ['cash', 'card'])
            ->whereNotIn('status', ['paid', 'canceled'])
            ->update(['status' => 'canceled']);

        DB::table('policy_payments')
            ->where('method', 'transfer')
            ->whereNotIn('status', ['scheduled', 'paid', 'canceled', 'overdue'])
            ->update(['status' => 'scheduled']);

        DB::table('policy_payments')
            ->where('method', 'no_method')
            ->whereNotIn('status', ['draft', 'overdue', 'canceled'])
            ->update(['status' => 'draft']);

        DB::table('policy_payments')
            ->whereIn('method', ['cash', 'card'])
            ->where('status', 'paid')
            ->whereNull('paid_at')
            ->update(['paid_at' => now()]);

        DB::table('policy_payments')
            ->where('method', 'transfer')
            ->whereIn('status', ['scheduled', 'overdue', 'canceled', 'paid'])
            ->whereNull('initiated_at')
            ->update(['initiated_at' => now()]);

        DB::statement("
        UPDATE policy_payments
        SET due_date =
            COALESCE(
                due_date,
                DATE_ADD(
                    COALESCE(initiated_at, created_at, NOW()),
                    INTERVAL FLOOR(5 + RAND()*3) DAY
                )
            )
    ");
    }

    public function down(): void
    {
    }
};
