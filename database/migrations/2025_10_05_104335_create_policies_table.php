<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number', 64)->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_offer_id')->constrained()->restrictOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('draft')->index();
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->decimal('premium_amount', 12, 2)->unsigned();
            $table->decimal('coverage_amount', 14, 2)->unsigned()->nullable();
            $table->string('payment_frequency', 32)->default('одноразово');
            $table->decimal('commission_rate', 5, 2)->unsigned()->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['client_id', 'status']);
            $table->comment('Страхові поліси, пов’язані з клієнтами та продуктами.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
