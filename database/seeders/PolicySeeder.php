<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Policy, Client, InsuranceOffer, PolicyPayment, User};
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $faker    = Faker::create('uk_UA');
        $clients  = Client::all();
        $offers   = InsuranceOffer::all();
        $managers = User::where('role', 'manager')->pluck('id')->toArray();

        if ($clients->isEmpty() || $offers->isEmpty()) {
            return;
        }

        $today = Carbon::today();

        $makePolicy = function (Client $client, InsuranceOffer $offer, array $opts = []) use ($faker, $managers, $today) {
            $effective     = $opts['effective']  ?? Carbon::now()->copy();
            $expiration    = $opts['expiration'] ?? (clone $effective)->addMonths($offer->duration_months);
            $dueDate       = $opts['due_date']   ?? (clone $effective)->addDays(7);
            $paymentStatus = $opts['payment_status'] ?? 'scheduled';
            $method        = $opts['method'] ?? Arr::random(['cash', 'card', 'transfer']);

            if ($paymentStatus === 'overdue' && $dueDate->gte($today)) {
                $dueDate = Carbon::today()->subDay();
            }
            if ($paymentStatus === 'scheduled' && $dueDate->lt($today)) {
                $dueDate = Carbon::today()->addDays(rand(1, 7));
            }
            if ($paymentStatus === 'paid' && $dueDate->gt($today)) {
                $dueDate = Carbon::today();
            }
            if ($paymentStatus === 'canceled' && $dueDate->gt($today)) {
                $dueDate = Carbon::today()->subDay();
            }

            $totalPrice     = round($offer->price * $offer->duration_months, 2);
            $commissionRate = $opts['commission_rate'] ?? $faker->randomFloat(2, 1, 8);
            $commission     = round($totalPrice * ($commissionRate / 100), 2);
            $finalPrice     = $totalPrice + $commission;

            $policy = Policy::create([
                'policy_number'      => 'POL-' . strtoupper(uniqid()),
                'client_id'          => $client->id,
                'insurance_offer_id' => $offer->id,
                'agent_id'           => count($managers) ? Arr::random($managers) : null,
                'status'             => 'draft',
                'effective_date'     => $effective,
                'expiration_date'    => $expiration,
                'premium_amount'     => $finalPrice,
                'coverage_amount'    => $offer->coverage_amount,
                'payment_frequency'  => 'once',
                'commission_rate'    => $commissionRate,
                'notes'              => null,
            ]);

            $paidAt = match ($paymentStatus) {
                'paid'      => (clone $effective)->addDays(min(7, max(0, rand(0, 7))))->setTime(rand(8, 18), rand(0, 59), rand(0, 59)),
                'scheduled' => Carbon::now()->setTime(rand(8, 20), rand(0, 59), rand(0, 59)),
                default     => null,
            };

            $payment = PolicyPayment::create([
                'policy_id'             => $policy->id,
                'due_date'              => $dueDate,
                'paid_at'               => $paidAt,
                'amount'                => $finalPrice,
                'method'                => $method,
                'status'                => $paymentStatus,
                'transaction_reference' => strtoupper($faker->bothify('TRX#######')),
                'notes'                 => null,
            ]);

            $status = match (true) {
                $payment->status === 'canceled' => 'canceled',
                $payment->status === 'paid' && $today->gte($expiration) => 'completed',
                $payment->status === 'paid' && $today->between($effective, $expiration) => 'active',
                in_array($payment->status, ['scheduled', 'overdue'], true) => 'draft',
                default => 'draft',
            };

            if ($status === 'completed' && $payment->status !== 'paid') {
                $status = 'canceled';
            }
            if ($status === 'canceled' && $payment->status === 'paid') {
                $status = $today->gte($expiration) ? 'completed' : 'active';
            }

            $policy->update(['status' => $status]);
        };

        $pickClient = fn () => $clients->random();
        $pickOffer  = fn () => $offers->random();

        $makePolicy($pickClient(), $pickOffer(), [
            'effective'      => Carbon::today()->addDays(1),
            'expiration'     => Carbon::today()->addMonths(3),
            'due_date'       => Carbon::today()->addDays(5),
            'payment_status' => 'scheduled',
            'method'         => 'transfer',
        ]);

        $makePolicy($pickClient(), $pickOffer(), [
            'effective'      => Carbon::today()->subDays(7),
            'expiration'     => Carbon::today()->addMonth(),
            'due_date'       => Carbon::today()->subDays(3),
            'payment_status' => 'overdue',
            'method'         => 'card',
        ]);

        $makePolicy($pickClient(), $pickOffer(), [
            'effective'      => Carbon::today()->subDays(10),
            'expiration'     => Carbon::today()->addMonths(2),
            'due_date'       => Carbon::today(),
            'payment_status' => 'paid',
            'method'         => 'cash',
        ]);

        $makePolicy($pickClient(), $pickOffer(), [
            'effective'      => Carbon::today()->subMonths(3)->subDays(5),
            'expiration'     => Carbon::today()->subDay(),
            'due_date'       => Carbon::today()->subMonths(3),
            'payment_status' => 'paid',
            'method'         => 'card',
        ]);

        $makePolicy($pickClient(), $pickOffer(), [
            'effective'      => Carbon::today()->subMonths(2),
            'expiration'     => Carbon::today()->addMonth(),
            'due_date'       => Carbon::today()->subDay(),
            'payment_status' => 'canceled',
            'method'         => 'card',
        ]);

        foreach ($clients as $client) {
            $count = rand(1, 2);
            for ($i = 0; $i < $count; $i++) {
                $offer      = $offers->random();
                $effective  = Carbon::instance($faker->dateTimeBetween('-1 year', 'now'));
                $expiration = (clone $effective)->addMonths($offer->duration_months);
                $dueDate    = (clone $effective)->addDays(7);
                $method     = Arr::random(['cash', 'card', 'transfer']);

                $paymentStatus = Arr::random(['paid', 'canceled', 'scheduled', 'overdue']);

                if ($paymentStatus === 'overdue') {
                    $dueDate = Carbon::today()->subDays(rand(1, 10));
                } elseif ($paymentStatus === 'scheduled') {
                    $dueDate = Carbon::today()->addDays(rand(1, 10));
                } elseif ($paymentStatus === 'paid') {
                    $dueDate = Carbon::today()->subDays(rand(0, 3));
                } elseif ($paymentStatus === 'canceled') {
                    $dueDate = Carbon::today()->subDays(rand(0, 5));
                }

                if ($paymentStatus === 'paid' && $expiration->lte($today)) {
                    $effective = Carbon::today()->subMonths(3);
                    $expiration = Carbon::today()->addMonth();
                }
                if ($paymentStatus === 'canceled' && $expiration->lte($today)) {
                    $effective = Carbon::today()->subMonth();
                    $expiration = Carbon::today()->addMonths(2);
                }

                $makePolicy($client, $offer, [
                    'effective'      => $effective,
                    'expiration'     => $expiration,
                    'due_date'       => $dueDate,
                    'payment_status' => $paymentStatus,
                    'method'         => $method,
                ]);
            }
        }

        $this->command?->info('Policies:');
        DB::table('policies')->select('status', DB::raw('count(*) c'))->groupBy('status')->get()
            ->each(fn($r) => $this->command?->info(" - {$r->status}: {$r->c}"));

        $this->command?->info('Payments:');
        DB::table('policy_payments')->select('status', DB::raw('count(*) c'))->groupBy('status')->get()
            ->each(fn($r) => $this->command?->info(" - {$r->status}: {$r->c}"));
    }
}
