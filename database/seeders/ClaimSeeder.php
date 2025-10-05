<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Policy;
use App\Models\Claim;
use App\Models\ClaimNote;
use Faker\Factory as Faker;
use Carbon\Carbon;

class ClaimSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('uk_UA');
        $policies = Policy::all();

        foreach ($policies as $policy) {
            if (rand(0, 1)) {
                $claim = Claim::create([
                    'claim_number' => 'CLM-' . strtoupper(uniqid()),
                    'policy_id' => $policy->id,
                    'reported_by_id' => null,
                    'status' => $faker->randomElement(['reviewing', 'approved', 'rejected', 'paid']),
                    'reported_at' => now()->subDays(rand(5, 60)),
                    'loss_occurred_at' => now()->subDays(rand(10, 90)),
                    'loss_location' => $faker->city(),
                    'cause' => $faker->sentence(3),
                    'amount_claimed' => $faker->numberBetween(5000, 80000),
                    'amount_reserve' => $faker->numberBetween(1000, 15000),
                    'amount_paid' => $faker->numberBetween(0, 15000),
                    'description' => $faker->paragraph(),
                    'metadata' => json_encode(['inspector' => $faker->name(), 'priority' => rand(1, 3)]),
                ]);

                for ($i = 0; $i < rand(1, 3); $i++) {
                    ClaimNote::create([
                        'claim_id' => $claim->id,
                        'user_id' => null,
                        'visibility' => 'internal',
                        'note' => $faker->sentence(),
                    ]);
                }
            }
        }
    }
}
