<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->string('type', 20)->default('individual')->index(); 
        $table->string('status', 32)->default('lead')->index(); 

        $table->string('first_name');
        $table->string('last_name');
        $table->string('middle_name')->nullable();

        $table->string('company_name')->nullable();
        $table->string('primary_email')->nullable();
        $table->string('primary_phone', 32)->nullable();

        $table->string('document_number'); 
        $table->string('tax_id', 64)->nullable(); 
        $table->date('date_of_birth'); 

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

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
