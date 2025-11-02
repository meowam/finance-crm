<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@insurance.local'],
            [
                'name' => 'Головний адміністратор',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        for ($i = 1; $i <= 3; $i++) {
            User::firstOrCreate(
                ['email' => "supervisor{$i}@insurance.local"],
                [
                    'name' => "Керівник {$i}",
                    'password' => Hash::make('supervisor123'),
                    'role' => 'supervisor',
                    'is_active' => true,
                ]
            );
        }

        for ($i = 1; $i <= 7; $i++) {
            User::firstOrCreate(
                ['email' => "manager{$i}@insurance.local"],
                [
                    'name' => "Менеджер {$i}",
                    'password' => Hash::make('manager123'),
                    'role' => 'manager',
                    'is_active' => true,
                ]
            );
        }
    }
}
