<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('policy_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->string('status', 32)->default('заплановано')->index();
            $table->string('method', 32)->nullable();
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->comment('Графік оплат і фактичні транзакції за полісами.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_payments');
    }
};
