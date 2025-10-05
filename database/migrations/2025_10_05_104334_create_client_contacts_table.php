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
        // ---- Додаткові контакти клієнтів ----
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32); // email, phone, telegram, etc.
            $table->string('value');
            $table->string('label', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->comment('Додаткові канали зв’язку, пов’язані з клієнтом.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
    }
};
