<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsuranceOffer;
use App\Models\InsuranceProduct;
use App\Models\InsuranceCompany;
use Faker\Factory as Faker;

class InsuranceOfferSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('uk_UA');
        $products = InsuranceProduct::all();
        $companies = InsuranceCompany::all();
        $possibleDurations = [1, 3, 6, 12]; 

        foreach ($products as $product) {
            foreach ($companies as $company) {
                $basePrice = $faker->numberBetween(800, 2500);

                $tariffs = [
                    [
                        'name' => 'Базовий',
                        'price_multiplier' => 1.0,
                        'coverage_multiplier' => 35,
                        'franchise_percent' => 0.15,
                        'benefits' => 'Базове покриття основних ризиків. Швидке онлайн-оформлення.',
                        'conditions' => ['support' => '24/7', 'documents' => 'електронний поліс'],
                    ],
                    [
                        'name' => 'Комфорт+',
                        'price_multiplier' => 1.6,
                        'coverage_multiplier' => 60,
                        'franchise_percent' => 0.07,
                        'benefits' => 'Розширене покриття з допомогою на дорозі.',
                        'conditions' => ['support' => '24/7', 'road_assistance' => true],
                    ],
                    [
                        'name' => 'Преміум',
                        'price_multiplier' => 2.4,
                        'coverage_multiplier' => 90,
                        'franchise_percent' => 0,
                        'benefits' => 'Максимальне покриття, VIP-підтримка, без франшизи.',
                        'conditions' => ['support' => '24/7', 'vip_service' => true, 'cashback' => '5%'],
                    ],
                ];

                foreach ($tariffs as $tariff) {
                    $duration = $possibleDurations[array_rand($possibleDurations)];

                    // Унікальність комбінації (компанія + продукт + тариф + тривалість)
                    $exists = InsuranceOffer::where([
                        ['insurance_company_id', '=', $company->id],
                        ['insurance_product_id', '=', $product->id],
                        ['offer_name', '=', $tariff['name']],
                        ['duration_months', '=', $duration],
                    ])->exists();

                    if ($exists) {
                        continue;
                    }

                    InsuranceOffer::create([
                        'insurance_product_id' => $product->id,
                        'insurance_company_id' => $company->id,
                        'offer_name' => $tariff['name'],
                        'price' => round($basePrice * $tariff['price_multiplier'], 2),
                        'coverage_amount' => round($basePrice * $tariff['coverage_multiplier'], 2),
                        'duration_months' => $duration,
                        'franchise' => round($basePrice * $tariff['franchise_percent'], 2),
                        'benefits' => $tariff['benefits'],
                        'conditions' => json_encode($tariff['conditions'], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }
        }
    }
}
