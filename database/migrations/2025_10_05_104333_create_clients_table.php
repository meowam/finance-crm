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
        // ---- Клієнти ----
        Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->string('type', 20)->default('individual')->index(); // individual / company
        $table->string('status', 32)->default('lead')->index(); // lead / active / archived

        $table->string('first_name');
        $table->string('last_name');
        $table->string('middle_name')->nullable();

        $table->string('company_name')->nullable();
        $table->string('primary_email')->nullable();
        $table->string('primary_phone', 32)->nullable();

        $table->string('document_number'); // дві латинські літери + 6 цифр
        $table->string('tax_id', 64)->nullable(); // ІПН або код ЄДРПОУ
        $table->date('date_of_birth'); // тепер обов’язкове

        $table->enum('preferred_contact_method', ['phone', 'email'])->nullable();
        $table->string('city')->nullable();
        $table->string('address_line')->nullable();
        $table->string('source')->nullable();

        $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->text('notes')->nullable()->default(null);

        $table->timestamps();
        $table->softDeletes();
        $table->comment('Клієнти CRM: фізичні та юридичні особи, які купують страхові продукти.');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
