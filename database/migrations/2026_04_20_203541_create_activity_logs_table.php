<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_role')->nullable();

            $table->string('action', 50);

            $table->nullableMorphs('subject');

            $table->string('subject_type_label')->nullable();
            $table->string('subject_label')->nullable();

            $table->text('description')->nullable();

            $table->json('before')->nullable();
            $table->json('after')->nullable();

            $table->timestamps();

            $table->index(['actor_role', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};