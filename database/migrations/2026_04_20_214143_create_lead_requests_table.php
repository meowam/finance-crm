<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_requests', function (Blueprint $table) {
            $table->id();

            $table->string('type')->default('individual'); // individual | company

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('middle_name')->nullable();

            $table->string('company_name')->nullable();

            $table->string('phone');
            $table->string('email')->nullable();

            $table->string('interest')->nullable();

            $table->string('source')->default('online'); // office | online | recommendation | landing | other
            $table->string('status')->default('new'); // new | in_progress | converted | rejected

            $table->text('comment')->nullable();

            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('converted_client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['assigned_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_requests');
    }
};