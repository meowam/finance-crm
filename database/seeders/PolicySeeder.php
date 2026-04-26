<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\InsuranceOffer;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PolicySeeder extends Seeder
{
    protected function commissionFor(string $offerName): float
    {
        return match (mb_strtolower(trim($offerName))) {
            'преміум', 'премиум', 'premium' => 0.00,
            'комфорт+', 'комфорт плюс', 'comfort+' => 1.50,
            'базовий', 'базовый', 'basic' => 3.00,
            default => 2.00,
        };
    }

    protected function priceWithCommission(InsuranceOffer $offer, float $rate): float
    {
        $base = (float) $offer->price * (int) $offer->duration_months;

        return round($base + ($base * ($rate / 100)), 2);
    }

    protected function dueFrom(?Carbon $base = null): string
    {
        $date = $base ?: now();

        return $date->copy()->addDays(rand(5, 7))->toDateString();
    }

    protected function resolvePolicyStatus(PaymentStatus|string|null $paymentStatus, Carbon $effective, Carbon $expiration): PolicyStatus
    {
        $today = Carbon::today();

        $status = $paymentStatus instanceof PaymentStatus
            ? $paymentStatus->value
            : (string) $paymentStatus;

        return match ($status) {
            PaymentStatus::Paid->value => $today->gte($expiration)
                ? PolicyStatus::Completed
                : PolicyStatus::Active,

            PaymentStatus::Overdue->value,
            PaymentStatus::Canceled->value,
            PaymentStatus::Refunded->value => PolicyStatus::Canceled,

            default => PolicyStatus::Draft,
        };
    }

    protected function resolvePolicyAgentId(Client $client, array $managers): ?int
    {
        if ($client->assigned_user_id && in_array((int) $client->assigned_user_id, $managers, true)) {
            return (int) $client->assigned_user_id;
        }

        return count($managers) ? Arr::random($managers) : null;
    }

    public function run(): void
    {
        Policy::$suppressAutoDraft = true;

        $faker = Faker::create('uk_UA');

        $clients = Client::query()->get();

        $offers = InsuranceOffer::query()
            ->with(['insuranceProduct'])
            ->get();

        $managers = User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if ($clients->isEmpty() || $offers->isEmpty()) {
            Policy::$suppressAutoDraft = false;

            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('policy_payments')->truncate();
        DB::table('policies')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $mk = function (Client $client, InsuranceOffer $offer, array $cfg) use ($managers) {
            $effective = Carbon::parse($cfg['effective']);
            $expiration = Carbon::parse($cfg['expiration']);
            $rate = $this->commissionFor((string) ($offer->offer_name ?? ''));
            $price = $this->priceWithCommission($offer, $rate);
            $agentId = $this->resolvePolicyAgentId($client, $managers);

            $policy = Policy::query()->create([
                'client_id' => $client->id,
                'insurance_offer_id' => $offer->id,
                'agent_id' => $agentId,
                'status' => PolicyStatus::Draft,
                'effective_date' => $effective,
                'expiration_date' => $expiration,
                'premium_amount' => $price,
                'coverage_amount' => $offer->coverage_amount,
                'payment_frequency' => 'once',
                'commission_rate' => $rate,
                'payment_due_at' => $effective->copy()->addDays(7),
            ]);

            $scenario = $cfg['scenario'];

            $paymentStatus = PaymentStatus::Paid;
            $paymentMethod = PaymentMethod::Cash;
            $initiatedAt = null;
            $paidAt = now()->subDay();
            $dueDate = $this->dueFrom($policy->created_at ? Carbon::parse($policy->created_at) : now());

            switch ($scenario) {
                case 'paid_cash':
                    $paymentStatus = PaymentStatus::Paid;
                    $paymentMethod = PaymentMethod::Cash;
                    $initiatedAt = null;
                    $paidAt = now()->subDay();
                    break;

                case 'paid_card':
                    $paymentStatus = PaymentStatus::Paid;
                    $paymentMethod = PaymentMethod::Card;
                    $initiatedAt = null;
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
                    $initiatedAt = null;
                    $paidAt = null;
                    break;

                case 'canceled_card':
                    $paymentStatus = PaymentStatus::Canceled;
                    $paymentMethod = PaymentMethod::Card;
                    $initiatedAt = null;
                    $paidAt = null;
                    break;

                case 'canceled_transfer':
                    $paymentStatus = PaymentStatus::Canceled;
                    $paymentMethod = PaymentMethod::Transfer;
                    $initiatedAt = now()->subDay();
                    $paidAt = null;
                    $dueDate = $this->dueFrom($initiatedAt);
                    break;

                case 'refunded_card':
                    $paymentStatus = PaymentStatus::Refunded;
                    $paymentMethod = PaymentMethod::Card;
                    $initiatedAt = null;
                    $paidAt = now()->subDays(3);
                    $dueDate = now()->subDays(4)->toDateString();
                    break;

                case 'refunded_transfer':
                    $paymentStatus = PaymentStatus::Refunded;
                    $paymentMethod = PaymentMethod::Transfer;
                    $initiatedAt = now()->subDays(5);
                    $paidAt = now()->subDays(4);
                    $dueDate = now()->subDays(6)->toDateString();
                    break;

                case 'no_method_draft':
                    $paymentStatus = PaymentStatus::Draft;
                    $paymentMethod = PaymentMethod::NoMethod;
                    $initiatedAt = null;
                    $paidAt = null;
                    $dueDate = $this->dueFrom($policy->created_at ? Carbon::parse($policy->created_at) : now());
                    break;
            }

            $payment = PolicyPayment::query()->create([
                'policy_id' => $policy->id,
                'amount' => $price,
                'method' => $paymentMethod,
                'status' => $paymentStatus,
                'initiated_at' => $initiatedAt,
                'paid_at' => $paidAt,
                'due_date' => $dueDate,
            ]);

            $policy->update([
                'status' => $this->resolvePolicyStatus($payment->status, $effective, $expiration),
            ]);
        };

        $pickClient = fn () => $clients->random();
        $pickOffer = fn () => $offers->random();

        $o1 = $pickOffer();
        $mk($pickClient(), $o1, [
            'effective' => Carbon::today()->subDays(2),
            'expiration' => Carbon::today()->addMonths($o1->duration_months),
            'scenario' => 'paid_cash',
        ]);

        $o2 = $pickOffer();
        $mk($pickClient(), $o2, [
            'effective' => Carbon::today()->subDays(5),
            'expiration' => Carbon::today()->addMonths($o2->duration_months),
            'scenario' => 'paid_card',
        ]);

        $o3 = $pickOffer();
        $mk($pickClient(), $o3, [
            'effective' => Carbon::today()->subDays(7),
            'expiration' => Carbon::today()->addMonths($o3->duration_months),
            'scenario' => 'paid_transfer',
        ]);

        $o4 = $pickOffer();
        $mk($pickClient(), $o4, [
            'effective' => Carbon::today(),
            'expiration' => Carbon::today()->addMonths($o4->duration_months),
            'scenario' => 'scheduled_transfer',
        ]);

        $o5 = $pickOffer();
        $mk($pickClient(), $o5, [
            'effective' => Carbon::today()->subDays(15),
            'expiration' => Carbon::today()->addMonths($o5->duration_months),
            'scenario' => 'overdue_transfer',
        ]);

        $o6 = $pickOffer();
        $mk($pickClient(), $o6, [
            'effective' => Carbon::today()->addDays(1),
            'expiration' => Carbon::today()->addMonths($o6->duration_months + 1),
            'scenario' => 'canceled_cash',
        ]);

        $o7 = $pickOffer();
        $mk($pickClient(), $o7, [
            'effective' => Carbon::today()->addDays(2),
            'expiration' => Carbon::today()->addMonths($o7->duration_months + 1),
            'scenario' => 'canceled_card',
        ]);

        $o8 = $pickOffer();
        $mk($pickClient(), $o8, [
            'effective' => Carbon::today()->addDays(3),
            'expiration' => Carbon::today()->addMonths($o8->duration_months + 1),
            'scenario' => 'canceled_transfer',
        ]);

        $o9 = $pickOffer();
        $mk($pickClient(), $o9, [
            'effective' => Carbon::today()->addDays(1),
            'expiration' => Carbon::today()->addMonths($o9->duration_months),
            'scenario' => 'no_method_draft',
        ]);

        $o10 = $pickOffer();
        $mk($pickClient(), $o10, [
            'effective' => Carbon::today()->addDays(2),
            'expiration' => Carbon::today()->addMonths($o10->duration_months),
            'scenario' => 'no_method_draft',
        ]);

        $o11 = $pickOffer();
        $mk($pickClient(), $o11, [
            'effective' => Carbon::today()->subDays(10),
            'expiration' => Carbon::today()->addMonths($o11->duration_months),
            'scenario' => 'refunded_card',
        ]);

        $o12 = $pickOffer();
        $mk($pickClient(), $o12, [
            'effective' => Carbon::today()->subDays(12),
            'expiration' => Carbon::today()->addMonths($o12->duration_months),
            'scenario' => 'refunded_transfer',
        ]);

        foreach ($clients as $client) {
            $count = rand(2, 4);

            for ($i = 0; $i < $count; $i++) {
                $offer = $offers->random();
                $effective = Carbon::today()->subDays(rand(0, 60));
                $expiration = $effective->copy()->addMonths($offer->duration_months);

                $scenarioPool = [
                    'paid_cash',
                    'paid_cash',
                    'paid_cash',
                    'paid_card',
                    'paid_card',
                    'paid_transfer',
                    'scheduled_transfer',
                    'canceled_cash',
                    'canceled_card',
                    'canceled_transfer',
                    'refunded_card',
                    'refunded_transfer',
                ];

                if (rand(1, 10) <= 2) {
                    $scenarioPool[] = 'no_method_draft';
                }

                if (rand(1, 10) <= 1) {
                    $scenarioPool[] = 'overdue_transfer';
                }

                $scenario = Arr::random($scenarioPool);

                $mk($client, $offer, [
                    'effective' => $effective,
                    'expiration' => $expiration,
                    'scenario' => $scenario,
                ]);
            }
        }

        DB::table('policy_payments')
            ->join('policies', 'policies.id', '=', 'policy_payments.policy_id')
            ->where('policies.status', PolicyStatus::Canceled->value)
            ->where('policy_payments.status', PaymentStatus::Scheduled->value)
            ->update([
                'policy_payments.status' => PaymentStatus::Canceled->value,
                'policy_payments.updated_at' => now(),
            ]);

        $this->command?->info('Policies:');

        DB::table('policies')
            ->select('status', DB::raw('count(*) c'))
            ->groupBy('status')
            ->get()
            ->each(fn ($row) => $this->command?->info(" - {$row->status}: {$row->c}"));

        $this->command?->info('Payments:');

        DB::table('policy_payments')
            ->select('status', DB::raw('count(*) c'))
            ->groupBy('status')
            ->get()
            ->each(fn ($row) => $this->command?->info(" - {$row->status}: {$row->c}"));

        Policy::$suppressAutoDraft = false;
    }
}