<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsuranceCategory;

class InsuranceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'AUTO', 'name' => 'Автострахування', 'description' => 'Захист транспортного засобу та відповідальності.'],
            ['code' => 'HEALTH', 'name' => 'Медичне страхування', 'description' => 'Медичні послуги та компенсація лікування.'],
            ['code' => 'HOME', 'name' => 'Страхування житла', 'description' => 'Захист від пожежі, затоплення, крадіжки.'],
            ['code' => 'TRAVEL', 'name' => 'Страхування подорожей', 'description' => 'Медичне покриття під час подорожей.'],
            ['code' => 'CHILD', 'name' => 'Страхування дітей', 'description' => 'Захист здоров’я та життя дитини.'],
        ];

        foreach ($categories as $cat) {
            InsuranceCategory::create($cat);
        }
    }
}
