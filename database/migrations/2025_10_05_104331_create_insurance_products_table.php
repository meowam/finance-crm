<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('insurance_products', function (Blueprint $table) {
        $table->id();

        $table->foreignId('category_id')
            ->nullable()
            ->constrained('insurance_categories')
            ->nullOnDelete();

        $table->string('code', 64)->unique(); 
        $table->string('name'); 
        $table->text('description')->nullable(); 

        $table->boolean('sales_enabled')->default(true); 
        $table->json('metadata')->nullable(); 

        $table->timestamps();

        $table->comment('Каталог страхових продуктів (без цін, лише шаблони типів страхування).');
    });

    }


    public function down(): void
    {
        Schema::dropIfExists('insurance_products');
    }
};
