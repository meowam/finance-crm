<?php

namespace Database\Seeders;

use App\Models\InsuranceCompany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class InsuranceCompanySeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('logos');

        $companies = [
            [
                'name' => 'ТАС',
                'short' => 'TAS',
                'license_number' => 'UA-TAS-010',
                'country' => 'Україна',
                'contact_email' => 'info@sgtas.ua',
                'contact_phone' => '+380444123456',
                'website' => 'https://sgtas.ua',
                'logo_path' => 'logos/tas.svg',
                'bg' => '#F59E0B',
                'fg' => '#111827',
            ],
            [
                'name' => 'UNIQA Україна',
                'short' => 'UNIQA',
                'license_number' => 'UA-UNI-002',
                'country' => 'Україна',
                'contact_email' => 'support@uniqa.ua',
                'contact_phone' => '+380442345678',
                'website' => 'https://uniqa.ua',
                'logo_path' => 'logos/uniqa_ua.svg',
                'bg' => '#2563EB',
                'fg' => '#FFFFFF',
            ],
            [
                'name' => 'PZU Україна',
                'short' => 'PZU',
                'license_number' => 'UA-PZU-003',
                'country' => 'Україна',
                'contact_email' => 'for-pzu@pzu.com.ua',
                'contact_phone' => '+380800503115',
                'website' => 'https://pzu.com.ua',
                'logo_path' => 'logos/pzu_ua.svg',
                'bg' => '#1D4ED8',
                'fg' => '#FFFFFF',
            ],
            [
                'name' => 'ІНГО',
                'short' => 'INGO',
                'license_number' => 'UA-INGO-004',
                'country' => 'Україна',
                'contact_email' => 'info@ingo.ua',
                'contact_phone' => '+380800215553',
                'website' => 'https://ingo.ua',
                'logo_path' => 'logos/ingo.svg',
                'bg' => '#0F172A',
                'fg' => '#FFFFFF',
            ],
            [
                'name' => 'Арсенал Страхування',
                'short' => 'ARS',
                'license_number' => 'UA-ARS-011',
                'country' => 'Україна',
                'contact_email' => 'info@arsenal-ic.ua',
                'contact_phone' => '+380442234567',
                'website' => 'https://arsenal-ic.ua',
                'logo_path' => 'logos/arsenal.svg',
                'bg' => '#DC2626',
                'fg' => '#FFFFFF',
            ],
        ];

        foreach ($companies as $company) {
            Storage::disk('public')->put(
                $company['logo_path'],
                $this->makeLogoSvg(
                    label: $company['short'],
                    background: $company['bg'],
                    foreground: $company['fg'],
                )
            );

            InsuranceCompany::updateOrCreate(
                ['name' => $company['name']],
                [
                    'name' => $company['name'],
                    'license_number' => $company['license_number'],
                    'country' => $company['country'],
                    'contact_email' => $company['contact_email'],
                    'contact_phone' => $company['contact_phone'],
                    'website' => $company['website'],
                    'logo_path' => $company['logo_path'],
                ]
            );
        }
    }

    protected function makeLogoSvg(string $label, string $background, string $foreground): string
    {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $safeBackground = htmlspecialchars($background, ENT_QUOTES, 'UTF-8');
        $safeForeground = htmlspecialchars($foreground, ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="140" viewBox="0 0 320 140" role="img" aria-label="{$safeLabel}">
    <rect width="320" height="140" rx="28" fill="{$safeBackground}"/>
    <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle"
          font-family="Arial, Helvetica, sans-serif"
          font-size="44"
          font-weight="700"
          fill="{$safeForeground}">
        {$safeLabel}
    </text>
</svg>
SVG;
    }
}