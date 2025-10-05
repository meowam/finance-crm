<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsuranceCompany;

class InsuranceCompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name' => 'ТАС',
                'license_number' => 'UA-TAS-010',
                'country' => 'Україна',
                'contact_email' => 'info@sgtas.ua',
                'contact_phone' => '+380444123456',  
                'website' => 'https://sgtas.ua',
                'logo_path' => 'logos/tas.png',
            ],
            [
                'name' => 'UNIQA Україна',
                'license_number' => 'UA-UNI-002',
                'country' => 'Україна',
                'contact_email' => 'support@uniqa.ua',
                'contact_phone' => '+380442345678',
                'website' => 'https://uniqa.ua',
                'logo_path' => 'logos/uniqa_ua.png',
            ],
            [
                'name' => 'PZU Україна',
                'license_number' => 'UA-PZU-003',
                'country' => 'Україна',
                'contact_email' => 'for-pzu@pzu.com.ua',
                'contact_phone' => '+380800503115',
                'website' => 'https://pzu.com.ua',
                'logo_path' => 'logos/pzu_ua.png',
            ],
            [
                'name' => 'ІНГО',
                'license_number' => 'UA-INGO-004',
                'country' => 'Україна',
                'contact_email' => 'info@ingo.ua',
                'contact_phone' => '+380800215553',
                'website' => 'https://ingo.ua',
                'logo_path' => 'logos/ingo.png',
            ],
            [
                'name' => 'Арсенал Страхування',
                'license_number' => 'UA-ARS-011',
                'country' => 'Україна',
                'contact_email' => 'info@arsenal-ic.ua',
                'contact_phone' => '+380442234567',  
                'website' => 'https://arsenal-ic.ua',
                'logo_path' => 'logos/arsenal.png',
            ],
        ];

        foreach ($companies as $comp) {
            InsuranceCompany::updateOrCreate(
                ['name' => $comp['name']],
                $comp
            );
        }
    }
}
