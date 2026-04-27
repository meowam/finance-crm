<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoProblemRecordsSeeder extends Seeder
{
    public function run(): void
    {
        $inactiveManager = User::query()->updateOrCreate(
            ['email' => 'inactive.manager@insurance.local'],
            [
                'name' => 'Олександр Мельник',
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'is_active' => false,
            ]
        );

        LeadRequest::query()->updateOrCreate(
            ['email' => 'olena.koval.demo@example.com'],
            [
                'type' => 'individual',
                'first_name' => 'Олена',
                'last_name' => 'Коваль',
                'middle_name' => 'Ігорівна',
                'company_name' => null,
                'phone' => '+380671110001',
                'interest' => 'КАСКО для нового автомобіля',
                'source' => 'landing',
                'status' => 'new',
                'comment' => 'Клієнтка залишила заявку на сайті та очікує консультацію щодо вартості КАСКО.',
                'assigned_user_id' => $inactiveManager->id,
                'converted_client_id' => null,
            ]
        );

        Client::query()->updateOrCreate(
            ['primary_email' => 'andrii.shevchenko.demo@example.com'],
            [
                'type' => 'individual',
                'status' => 'active',
                'first_name' => 'Андрій',
                'last_name' => 'Шевченко',
                'middle_name' => 'Петрович',
                'company_name' => null,
                'primary_phone' => '+380671110002',
                'document_number' => 'AS123456',
                'tax_id' => '1112223334',
                'date_of_birth' => now()->subYears(35)->toDateString(),
                'preferred_contact_method' => 'phone',
                'city' => 'Київ',
                'address_line' => 'вул. Січових Стрільців, 21',
                'source' => 'office',
                'assigned_user_id' => $inactiveManager->id,
                'notes' => 'Активний клієнт, який потребує перепризначення відповідального менеджера після деактивації попереднього менеджера.',
            ]
        );

        $this->command?->info('Demo problem records created:');
        $this->command?->info(' - inactive manager: inactive.manager@insurance.local');
        $this->command?->info(' - active lead assigned to inactive manager: olena.koval.demo@example.com');
        $this->command?->info(' - active client assigned to inactive manager: andrii.shevchenko.demo@example.com');
    }
}