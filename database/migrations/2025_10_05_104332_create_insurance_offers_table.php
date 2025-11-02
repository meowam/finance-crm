<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('insurance_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('insurance_product_id')->constrained('insurance_products')->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained('insurance_companies')->cascadeOnDelete();

            $table->string('offer_name'); 
            $table->decimal('price', 10, 2)->unsigned();
            $table->decimal('coverage_amount', 14, 2)->unsigned();
            $table->unsignedSmallInteger('duration_months')->default(12);
            $table->decimal('franchise', 10, 2)->unsigned()->default(0);

            $table->text('benefits')->nullable();
            $table->json('conditions')->nullable();

            $table->timestamps();

            $table->unique(
                ['insurance_company_id', 'insurance_product_id', 'offer_name'],
                'unique_offer_per_company'
            );

            $table->comment('Пропозиції страхових компаній для конкретних продуктів.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_offers'); 
    }
};
