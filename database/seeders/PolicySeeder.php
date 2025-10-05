<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Policy, Client, InsuranceOffer, PolicyPayment, User};
use Faker\Factory as Faker;
use Carbon\Carbon;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('uk_UA');
        $clients = Client::all();
        $offers = InsuranceOffer::all();
        $managers = User::where('role', 'manager')->pluck('id')->toArray();

        foreach ($clients as $client) {
            for ($i = 0; $i < rand(1, 2); $i++) {
                $offer = $offers->random();

                $effective = Carbon::instance($faker->dateTimeBetween('-1 year', 'now'));
                $expiration = (clone $effective)->addMonths($offer->duration_months);

                // Ціна за весь період (ціна в офері = за 1 місяць)
                $totalPrice = round($offer->price * $offer->duration_months, 2);

                $commissionRate = $faker->randomFloat(2, 1, 10); // від 1% до 10%
                $commissionAmount = round($totalPrice * ($commissionRate / 100), 2);

                // Підсумкова сума з урахуванням комісії
                $finalPrice = $totalPrice + $commissionAmount;

                $policy = Policy::create([
                    'policy_number' => 'POL-' . strtoupper(uniqid()),
                    'client_id' => $client->id,
                    'insurance_offer_id' => $offer->id,
                    'agent_id' => count($managers) ? $faker->randomElement($managers) : null,
                    'status' => $faker->randomElement(['чернетка', 'активний', 'завершений', 'скасований']),
                    'effective_date' => $effective,
                    'expiration_date' => $expiration,
                    'premium_amount' => $finalPrice,
                    'coverage_amount' => $offer->coverage_amount,
                    'payment_frequency' => 'одноразово',
                    'commission_rate' => $commissionRate,
                    'notes' => null,
                ]);


                // Оплатити треба протягом 7 днів після оформлення
                $dueDate = (clone $effective)->addDays(7);
                $method = $faker->randomElement(['готівка', 'картка', 'переказ']);

                $status = match ($method) {
                    'готівка' => 'оплачено',
                    'картка' => $faker->randomElement(['оплачено', 'скасовано']),
                    'переказ' => $faker->randomElement(['оплачено', 'в обробці', 'скасовано']),
                };

                $paidAt = (clone $effective)
                    ->addDays(rand(0, 7))
                    ->setTime(rand(0, 24), rand(0, 59), rand(0, 59));

                if ($method === 'готівка') {
                    $paidAt = (clone $effective)
                        ->addDays(rand(0, 7))
                        ->setTime(rand(8, 18), rand(0, 59), rand(0, 59));
                } else {
                    $paidAt = (clone $effective)
                        ->addDays(rand(0, 7))
                        ->setTime(rand(0, 23), rand(0, 59), rand(0, 59));
                }

                PolicyPayment::create([
                    'policy_id' => $policy->id,
                    'due_date' => $dueDate,
                    'paid_at' => $paidAt,
                    'amount' => $finalPrice,
                    'method' => $method,
                    'status' => $status,
                    'transaction_reference' => strtoupper($faker->bothify('TRX#######')),
                    'notes' => null,
                ]);
            }
        }
    }
}
