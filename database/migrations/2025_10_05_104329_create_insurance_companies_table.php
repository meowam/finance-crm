<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('license_number')->nullable();
            $table->string('country', 64)->default('Україна');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
            $table->comment('Страхові компанії, які надають продукти.');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('insurance_companies');
    }
};
