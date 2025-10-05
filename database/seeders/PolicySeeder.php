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
        $today = Carbon::now();
        $startDate = Carbon::now()->subYear();
        $currentDate = clone $startDate;

        foreach ($clients as $client) {
            $currentDate = Carbon::instance($faker->dateTimeBetween('-1 year', Carbon::now()->toDateString()));
            for ($i = 0; $i < rand(1, 2); $i++) {
                $offer = $offers->random();

                // беремо наступну дату по порядку
                $effective = $currentDate->copy();
                $currentDate->addDays(rand(10, 25)); // зсуваємо базову дату вперед

                $expiration = (clone $effective)->addMonths($offer->duration_months);
                $dueDate = (clone $effective)->addDays(7);

                // Ціна за весь період (ціна в офері = за 1 місяць)
                $totalPrice = round($offer->price * $offer->duration_months, 2);
                $commissionRate = $faker->randomFloat(2, 1, 8); // від 1% до 8%
                $commissionAmount = round($totalPrice * ($commissionRate / 100), 2);
                $finalPrice = $totalPrice + $commissionAmount;

                $method = $faker->randomElement(['готівка', 'картка', 'переказ']);

                $paymentStatus = match ($method) {
                    'готівка' => 'оплачено',
                    'картка' => $faker->randomElement(['оплачено', 'скасовано']),
                    'переказ' => Carbon::now()->lessThanOrEqualTo((clone $effective)->addDays(7))
                        ? $faker->randomElement(['оплачено', 'в обробці'])
                        : $faker->randomElement(['оплачено', 'скасовано']),
                };


                $paidAt = match ($method) {
                    'готівка' => $effective->copy()
                        ->addDays(rand(0, 7))
                        ->setTime(rand(8, 18), rand(0, 59), rand(0, 59)),
                    default => $effective->copy()
                        ->addDays(rand(0, 7))
                        ->setTime(rand(0, 23), rand(0, 59), rand(0, 59)),
                };

                $policy = Policy::create([
                    'policy_number' => 'POL-' . strtoupper(uniqid()),
                    'client_id' => $client->id,
                    'insurance_offer_id' => $offer->id,
                    'agent_id' => count($managers) ? $faker->randomElement($managers) : null,
                    'status' => 'чернетка',
                    'effective_date' => $effective,
                    'expiration_date' => $expiration,
                    'premium_amount' => $finalPrice,
                    'coverage_amount' => $offer->coverage_amount,
                    'payment_frequency' => 'одноразово',
                    'commission_rate' => $commissionRate,
                    'notes' => null,
                ]);

                $payment = PolicyPayment::create([
                    'policy_id' => $policy->id,
                    'due_date' => $dueDate,
                    'paid_at' => $paidAt,
                    'amount' => $finalPrice,
                    'method' => $method,
                    'status' => $paymentStatus,
                    'transaction_reference' => strtoupper($faker->bothify('TRX#######')),
                    'notes' => null,
                ]);

                $policyStatus = match (true) {
                    $payment->status !== 'оплачено' && $today->greaterThan($payment->due_date) => 'скасований',
                    $payment->status !== 'оплачено' && $today->lessThanOrEqualTo($payment->due_date) => 'чернетка',
                    $payment->status === 'оплачено' && $today->greaterThanOrEqualTo($expiration) => 'завершений',
                    $payment->status === 'оплачено' && $today->between($effective, $expiration) => 'активний',
                    default => 'чернетка',
                };

                $policy->update(['status' => $policyStatus]);
            }
        }
    }
}
