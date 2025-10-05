<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---- Претензії (страхові випадки) ----
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number', 64)->unique();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('draft')->index(); // draft / reviewing / approved / rejected / paid
            $table->timestamp('reported_at')->nullable();
            $table->date('loss_occurred_at')->nullable();
            $table->string('loss_location')->nullable();
            $table->string('cause')->nullable();
            $table->decimal('amount_claimed', 12, 2)->unsigned()->nullable();
            $table->decimal('amount_reserve', 12, 2)->unsigned()->nullable();
            $table->decimal('amount_paid', 12, 2)->unsigned()->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->comment('Страхові претензії (випадки), подані за полісами.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
