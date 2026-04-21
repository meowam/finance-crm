<?php

namespace App\Services\Policies;

use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use App\Notifications\OverduePaymentNotification;
use Illuminate\Support\Facades\DB;

class PolicyDailyService
{
    public function run(float $rate): void
    {
        DB::transaction(function () use ($rate) {
            $newOverdueIds = array_merge(
                $this->markOverdueTransfer(),
                $this->markOverdueNoMethod(),
            );

            $this->cancelPoliciesWithoutPaid();
            $this->randomizeScheduledToPaid($rate);
            $this->activateOrCompletePaidPolicies();

            $this->notifyOverduePayments($newOverdueIds);
        }, 3);
    }

    protected function markOverdueTransfer(): array
    {
        $ids = DB::table('policy_payments')
            ->where('method', 'transfer')
            ->where('status', 'scheduled')
            ->whereDate('due_date', '<', now()->toDateString())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($ids === []) {
            return [];
        }

        DB::table('policy_payments')
            ->whereIn('id', $ids)
            ->update([
                'status' => 'overdue',
                'updated_at' => now(),
            ]);

        return $ids;
    }

    protected function markOverdueNoMethod(): array
    {
        $ids = DB::table('policy_payments as pp')
            ->join('policies as p', 'p.id', '=', 'pp.policy_id')
            ->where('pp.method', 'no_method')
            ->where('pp.status', 'draft')
            ->whereDate('p.payment_due_at', '<', now()->toDateString())
            ->pluck('pp.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($ids === []) {
            return [];
        }

        DB::table('policy_payments')
            ->whereIn('id', $ids)
            ->update([
                'status' => 'overdue',
                'updated_at' => now(),
            ]);

        return $ids;
    }

    protected function cancelPoliciesWithoutPaid(): void
    {
        DB::table('policies as p')
            ->whereDate('p.payment_due_at', '<', now()->toDateString())
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('policy_payments as pp')
                    ->whereColumn('pp.policy_id', 'p.id')
                    ->where('pp.status', 'paid');
            })
            ->update([
                'p.status' => 'canceled',
                'p.updated_at' => now(),
            ]);
    }

    protected function randomizeScheduledToPaid(float $rate): void
    {
        $ids = PolicyPayment::query()
            ->select('policy_payments.id')
            ->join('policies as p', 'p.id', '=', 'policy_payments.policy_id')
            ->where('policy_payments.method', 'transfer')
            ->where('policy_payments.status', 'scheduled')
            ->whereDate('policy_payments.due_date', '<=', now()->toDateString())
            ->where('p.status', '!=', 'canceled')
            ->pluck('policy_payments.id')
            ->all();

        if (empty($ids)) {
            return;
        }

        shuffle($ids);

        $take = max(0, (int) ceil(count($ids) * $rate));

        if ($take === 0) {
            return;
        }

        $pick = array_slice($ids, 0, $take);

        DB::table('policy_payments')
            ->whereIn('id', $pick)
            ->update([
                'status' => 'paid',
                'paid_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function activateOrCompletePaidPolicies(): void
    {
        $policyIds = PolicyPayment::query()
            ->select('policy_id')
            ->where('status', PaymentStatus::Paid->value)
            ->distinct()
            ->pluck('policy_id')
            ->all();

        if (empty($policyIds)) {
            return;
        }

        $toComplete = Policy::query()
            ->whereIn('id', $policyIds)
            ->whereDate('expiration_date', '<=', now()->toDateString())
            ->pluck('id')
            ->all();

        if (! empty($toComplete)) {
            DB::table('policies')
                ->whereIn('id', $toComplete)
                ->update([
                    'status' => PolicyStatus::Completed->value,
                    'updated_at' => now(),
                ]);
        }

        $toActivate = Policy::query()
            ->whereIn('id', $policyIds)
            ->whereNotIn('id', $toComplete)
            ->where('status', '!=', PolicyStatus::Canceled->value)
            ->pluck('id')
            ->all();

        if (! empty($toActivate)) {
            DB::table('policies')
                ->whereIn('id', $toActivate)
                ->update([
                    'status' => PolicyStatus::Active->value,
                    'updated_at' => now(),
                ]);
        }
    }

    protected function notifyOverduePayments(array $paymentIds): void
    {
        if ($paymentIds === []) {
            return;
        }

        $payments = PolicyPayment::query()
            ->with(['policy.client', 'policy.agent'])
            ->whereIn('id', $paymentIds)
            ->get();

        foreach ($payments as $payment) {
            $recipients = User::query()
                ->where('is_active', true)
                ->where(function ($query) use ($payment) {
                    $query->whereIn('role', ['admin', 'supervisor']);

                    if ($payment->policy?->agent_id) {
                        $query->orWhere('id', $payment->policy->agent_id);
                    }
                })
                ->get()
                ->unique('id');

            foreach ($recipients as $recipient) {
                $alreadySent = DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $recipient->id)
                    ->where('type', OverduePaymentNotification::class)
                    ->where('data->notification_type', 'payment_overdue')
                    ->where('data->payment_id', $payment->id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $recipient->notify(new OverduePaymentNotification($payment));
            }
        }
    }
}