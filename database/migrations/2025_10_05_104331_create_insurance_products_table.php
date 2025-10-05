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
        // ---- Страхові продукти ----
        Schema::create('insurance_products', function (Blueprint $table) {
        $table->id();

        $table->foreignId('category_id')
            ->nullable()
            ->constrained('insurance_categories')
            ->nullOnDelete();

        $table->string('code', 64)->unique(); // наприклад, AUTO_BASIC, HOME_PROTECT
        $table->string('name'); 
        $table->text('description')->nullable(); // короткий опис

        $table->boolean('sales_enabled')->default(true); // можна чи ні оформити
        $table->json('metadata')->nullable(); // додаткові поля (умови, винятки, коментарі)

        $table->timestamps();

        $table->comment('Каталог страхових продуктів (без цін, лише шаблони типів страхування).');
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_products');
    }
};
