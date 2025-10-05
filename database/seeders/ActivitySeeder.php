<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;
use App\Models\Client;
use Faker\Factory as Faker;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('uk_UA');
        $clients = Client::all();

        foreach ($clients as $client) {
            for ($i = 0; $i < rand(2, 5); $i++) {
                Activity::create([
                    'client_id' => $client->id,
                    'policy_id' => null,
                    'claim_id' => null,
                    'owner_id' => null,
                    'activity_type' => $faker->randomElement(['call', 'meeting', 'task', 'email']),
                    'subject' => $faker->sentence(4),
                    'description' => $faker->paragraph(),
                    'status' => $faker->randomElement(['open', 'done', 'canceled']),
                    'due_at' => now()->addDays(rand(-5, 15)),
                    'completed_at' => rand(0, 1) ? now()->subDays(rand(0, 10)) : null,
                    'metadata' => json_encode(['importance' => rand(1, 5)]),
                ]);
            }
        }
    }
}
