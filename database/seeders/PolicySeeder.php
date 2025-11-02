<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use Illuminate\Database\Seeder;
use App\Models\{Policy, Client, InsuranceOffer, PolicyPayment, User};
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PolicySeeder extends Seeder
{
    protected function commissionFor(string $offerName): float
    {
        return match (mb_strtolower(trim($offerName))) {
            'преміум', 'премиум', 'premium'        => 0.00,
            'комфорт+', 'комфорт плюс', 'comfort+' => 1.50,
            'базовий', 'базовый', 'basic'          => 3.00,
            default                                 => 2.00,
        };
    }

    protected function priceWithCommission(InsuranceOffer $offer, float $rate): float
    {
        $base = (float) $offer->price * (int) $offer->duration_months;
        return round($base + ($base * ($rate / 100)), 2);
    }

    protected function dueFrom(?Carbon $base = null): string
    {
        $b = $base ?: now();
        return $b->copy()->addDays(rand(5, 7))->toDateString();
    }

    protected function resolvePolicyStatus(PaymentStatus|string|null $paymentStatus, Carbon $effective, Carbon $expiration): PolicyStatus
    {
        $today = Carbon::today();
        $s = $paymentStatus instanceof PaymentStatus ? $paymentStatus->value : (string) $paymentStatus;

        return match ($s) {
            'paid'    => $today->gte($expiration) ? PolicyStatus::Completed : PolicyStatus::Active,
            'overdue' => PolicyStatus::Canceled,
            default   => PolicyStatus::Draft,
        };
    }

    public function run(): void
    {
        \App\Models\Policy::$suppressAutoDraft = true;

        $faker    = Faker::create('uk_UA');
        $clients  = Client::all();
        $offers   = InsuranceOffer::with(['insuranceProduct'])->get();
        $managers = User::where('role', 'manager')->pluck('id')->toArray();

        if ($clients->isEmpty() || $offers->isEmpty()) {
            \App\Models\Policy::$suppressAutoDraft = false;
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('policy_payments')->truncate();
        DB::table('policies')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $mk = function (Client $client, InsuranceOffer $offer, array $cfg) use ($managers) {
            $effective  = Carbon::parse($cfg['effective']);
            $expiration = Carbon::parse($cfg['expiration']);
            $rate       = $this->commissionFor((string) ($offer->offer_name ?? ''));
            $price      = $this->priceWithCommission($offer, $rate);

            $policy = Policy::create([
                'client_id'          => $client->id,
                'insurance_offer_id' => $offer->id,
                'agent_id'           => count($managers) ? Arr::random($managers) : null,
                'status'             => PolicyStatus::Draft,
                'effective_date'     => $effective,
                'expiration_date'    => $expiration,
                'premium_amount'     => $price,
                'coverage_amount'    => $offer->coverage_amount,
                'payment_frequency'  => 'once',
                'commission_rate'    => $rate,
                'payment_due_at'     => $effective->copy()->addDays(7),
            ]);

            $scenario = $cfg['scenario'];
            $paymentStatus = PaymentStatus::Paid;
            $paymentMethod = PaymentMethod::Cash;
            $initiatedAt = null;
            $paidAt = now()->subDay();
            $dueDate = $this->dueFrom($policy->created_at ?? now());

            switch ($scenario) {
                case 'paid_cash':
                    $paymentStatus = PaymentStatus::Paid;
                    $paymentMethod = PaymentMethod::Cash;
                    $paidAt = now()->subDay();
                    break;

                case 'paid_card':
                    $paymentStatus = PaymentStatus::Paid;
                    $paymentMethod = PaymentMethod::Card;
                    $paidAt = now()->subHours(6);
                    break;

                case 'paid_transfer':
                    $paymentStatus = PaymentStatus::Paid;
                    $paymentMethod = PaymentMethod::Transfer;
                    $initiatedAt = now()->subDays(2);
                    $paidAt = now()->subDay();
                    $dueDate = $this->dueFrom($initiatedAt);
                    break;

                case 'scheduled_transfer':
                    $paymentStatus = PaymentStatus::Scheduled;
                    $paymentMethod = PaymentMethod::Transfer;
                    $initiatedAt = now();
                    $paidAt = null;
                    $dueDate = $this->dueFrom($initiatedAt);
                    break;

                case 'overdue_transfer':
                    $paymentStatus = PaymentStatus::Overdue;
                    $paymentMethod = PaymentMethod::Transfer;
                    $initiatedAt = now()->subDays(10);
                    $paidAt = null;
                    $dueDate = now()->subDays(3)->toDateString();
                    break;

                case 'canceled_cash':
                    $paymentStatus = PaymentStatus::Canceled;
                    $paymentMethod = PaymentMethod::Cash;
                    $paidAt = null;
                    break;

                case 'canceled_card':
                    $paymentStatus = PaymentStatus::Canceled;
                    $paymentMethod = PaymentMethod::Card;
                    $paidAt = null;
                    break;

                case 'canceled_transfer':
                    $paymentStatus = PaymentStatus::Canceled;
                    $paymentMethod = PaymentMethod::Transfer;
                    $initiatedAt = now()->subDay();
                    $paidAt = null;
                    $dueDate = $this->dueFrom($initiatedAt);
                    break;

                case 'no_method_draft':
                    $paymentStatus = PaymentStatus::Draft;
                    $paymentMethod = PaymentMethod::NoMethod;
                    $initiatedAt = null;
                    $paidAt = null;
                    $dueDate = $this->dueFrom($policy->created_at ?? now());
                    break;
            }

            $payment = PolicyPayment::create([
                'policy_id'    => $policy->id,
                'amount'       => $price,
                'method'       => $paymentMethod,
                'status'       => $paymentStatus,
                'initiated_at' => $initiatedAt,
                'paid_at'      => $paidAt,
                'due_date'     => $dueDate,
            ]);

            $policy->update([
                'status' => $this->resolvePolicyStatus($payment->status, $effective, $expiration),
            ]);
        };

        $pickClient = fn() => $clients->random();
        $pickOffer  = fn() => $offers->random();

        $o1 = $pickOffer();
        $mk($pickClient(), $o1, [
            'effective' => Carbon::today()->subDays(2),
            'expiration'=> Carbon::today()->addMonths($o1->duration_months),
            'scenario'  => 'paid_cash',
        ]);

        $o2 = $pickOffer();
        $mk($pickClient(), $o2, [
            'effective' => Carbon::today()->subDays(5),
            'expiration'=> Carbon::today()->addMonths($o2->duration_months),
            'scenario'  => 'paid_card',
        ]);

        $o3 = $pickOffer();
        $mk($pickClient(), $o3, [
            'effective' => Carbon::today()->subDays(7),
            'expiration'=> Carbon::today()->addMonths($o3->duration_months),
            'scenario'  => 'paid_transfer',
        ]);

        $o4 = $pickOffer();
        $mk($pickClient(), $o4, [
            'effective' => Carbon::today(),
            'expiration'=> Carbon::today()->addMonths($o4->duration_months),
            'scenario'  => 'scheduled_transfer',
        ]);

        $o5 = $pickOffer();
        $mk($pickClient(), $o5, [
            'effective' => Carbon::today()->subDays(15),
            'expiration'=> Carbon::today()->addMonths($o5->duration_months),
            'scenario'  => 'overdue_transfer',
        ]);

        $o6 = $pickOffer();
        $mk($pickClient(), $o6, [
            'effective' => Carbon::today()->addDays(1),
            'expiration'=> Carbon::today()->addMonths($o6->duration_months + 1),
            'scenario'  => 'canceled_cash',
        ]);

        $o7 = $pickOffer();
        $mk($pickClient(), $o7, [
            'effective' => Carbon::today()->addDays(2),
            'expiration'=> Carbon::today()->addMonths($o7->duration_months + 1),
            'scenario'  => 'canceled_card',
        ]);

        $o8 = $pickOffer();
        $mk($pickClient(), $o8, [
            'effective' => Carbon::today()->addDays(3),
            'expiration'=> Carbon::today()->addMonths($o8->duration_months + 1),
            'scenario'  => 'canceled_transfer',
        ]);

        $o9 = $pickOffer();
        $mk($pickClient(), $o9, [
            'effective' => Carbon::today()->addDays(1),
            'expiration'=> Carbon::today()->addMonths($o9->duration_months),
            'scenario'  => 'no_method_draft',
        ]);

        $o10 = $pickOffer();
        $mk($pickClient(), $o10, [
            'effective' => Carbon::today()->addDays(2),
            'expiration'=> Carbon::today()->addMonths($o10->duration_months),
            'scenario'  => 'no_method_draft',
        ]);

        foreach ($clients as $c) {
            $count = rand(2, 4);
            for ($i = 0; $i < $count; $i++) {
                $of  = $offers->random();
                $eff = Carbon::today()->subDays(rand(0, 60));
                $exp = (clone $eff)->addMonths($of->duration_months);
                $scPool = [
                    'paid_cash','paid_cash','paid_cash',
                    'paid_card','paid_card',
                    'paid_transfer',
                    'scheduled_transfer',
                    'canceled_cash','canceled_card','canceled_transfer',
                ];
                if (rand(1, 10) <= 2) {
                    $scPool[] = 'no_method_draft';
                }
                $sc  = Arr::random($scPool);

                $mk($c, $of, [
                    'effective' => $eff,
                    'expiration'=> $exp,
                    'scenario'  => $sc,
                ]);
            }
        }

        $this->command?->info('Policies:');
        DB::table('policies')->select('status', DB::raw('count(*) c'))->groupBy('status')->get()
            ->each(fn($r) => $this->command?->info(" - {$r->status}: {$r->c}"));

        $this->command?->info('Payments:');
        DB::table('policy_payments')->select('status', DB::raw('count(*) c'))->groupBy('status')->get()
            ->each(fn($r) => $this->command?->info(" - {$r->status}: {$r->c}"));

        \App\Models\Policy::$suppressAutoDraft = false;
    }
}
