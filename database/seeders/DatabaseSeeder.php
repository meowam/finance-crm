<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            InsuranceCategorySeeder::class,
            InsuranceCompanySeeder::class,
            InsuranceProductSeeder::class,
            InsuranceOfferSeeder::class,
            ClientSeeder::class,
            ClientContactSeeder::class,
            PolicySeeder::class,
            PolicyPaymentSeeder::class,
            ClaimSeeder::class,
            // ActivitySeeder::class,
        ]);
    }
}
