<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('policy_payments', function (Blueprint $table) {
            $table->date('due_date')->nullable()->change();
            $table->timestamp('initiated_at')->nullable()->after('due_date');
            $table->enum('method', ['cash','card','transfer','no_method'])->default('no_method')->change();
            $table->enum('status', ['draft','scheduled','paid','overdue','canceled'])->default('draft')->change();
            $table->index(['policy_id','status']);
            $table->unique('transaction_reference');
        });
    }

    public function down(): void {
        Schema::table('policy_payments', function (Blueprint $table) {
            $table->dropUnique(['transaction_reference']);
            $table->dropIndex(['policy_id','status']);
            $table->dropColumn('initiated_at');
        });
    }
};
