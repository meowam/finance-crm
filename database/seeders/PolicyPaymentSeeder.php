<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Policy;
use App\Models\PolicyPayment;
use Carbon\Carbon;
use Faker\Factory as Faker;

class PolicyPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('uk_UA');
        $policies = Policy::all();

        foreach ($policies as $policy) {
            $count = $policy->payment_frequency === 'monthly' ? 12 : 4;
            for ($i = 0; $i < $count; $i++) {
                $due = Carbon::parse($policy->effective_date)->addMonths($i);
                PolicyPayment::create([
                    'policy_id' => $policy->id,
                    'due_date' => $due,
                    'paid_at' => rand(0, 1) ? $due->copy()->addDays(rand(0, 5)) : null,
                    'amount' => round($policy->premium_amount / $count, 2),
                    'status' => rand(0, 1) ? 'paid' : 'scheduled',
                    'method' => $faker->randomElement(['card', 'cash', 'transfer']),
                    'transaction_reference' => strtoupper($faker->bothify('TRX#######')),
                    'notes' => $faker->sentence(),
                ]);
            }
        }
    }
}
