<?php

namespace App\Services\Policies;

use App\Enums\PaymentStatus;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use App\Notifications\OverduePaymentNotification;
use Illuminate\Support\Facades\DB;

class PolicyDailyService
{
    public function run(float $rate): void
    {
        $newOverdueIds = DB::transaction(function () use ($rate) {
            $newOverdueIds = array_merge(
                $this->markOverdueTransfer(),
                $this->markOverdueNoMethod(),
            );

            $paidPolicyIds = $this->randomizeScheduledToPaid($rate);

            $this->cancelOtherUnfinishedPaymentsForPolicies($paidPolicyIds);
            $this->recomputeAllPolicyStatuses();

            return $newOverdueIds;
        }, 3);

        $this->notifyOverduePayments($newOverdueIds);
    }

    protected function markOverdueTransfer(): array
    {
        $ids = DB::table('policy_payments')
            ->where('method', 'transfer')
            ->where('status', PaymentStatus::Scheduled->value)
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
                'status' => PaymentStatus::Overdue->value,
                'updated_at' => now(),
            ]);

        return $ids;
    }

    protected function markOverdueNoMethod(): array
    {
        $ids = DB::table('policy_payments as pp')
            ->join('policies as p', 'p.id', '=', 'pp.policy_id')
            ->where('pp.method', 'no_method')
            ->where('pp.status', PaymentStatus::Draft->value)
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
                'status' => PaymentStatus::Overdue->value,
                'updated_at' => now(),
            ]);

        return $ids;
    }

    protected function randomizeScheduledToPaid(float $rate): array
    {
        $ids = PolicyPayment::query()
            ->select('policy_payments.id')
            ->join('policies as p', 'p.id', '=', 'policy_payments.policy_id')
            ->where('policy_payments.method', 'transfer')
            ->where('policy_payments.status', PaymentStatus::Scheduled->value)
            ->whereDate('policy_payments.due_date', '<=', now()->toDateString())
            ->where('p.status', '!=', 'canceled')
            ->pluck('policy_payments.id')
            ->all();

        if (empty($ids)) {
            return [];
        }

        shuffle($ids);

        $take = max(0, (int) ceil(count($ids) * $rate));

        if ($take === 0) {
            return [];
        }

        $pickedPaymentIds = array_slice($ids, 0, $take);

        $policyIds = PolicyPayment::query()
            ->whereIn('id', $pickedPaymentIds)
            ->pluck('policy_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        DB::table('policy_payments')
            ->whereIn('id', $pickedPaymentIds)
            ->update([
                'status' => PaymentStatus::Paid->value,
                'paid_at' => now(),
                'updated_at' => now(),
            ]);

        return $policyIds;
    }

    protected function cancelOtherUnfinishedPaymentsForPolicies(array $policyIds): void
    {
        if ($policyIds === []) {
            return;
        }

        $paidPaymentIds = DB::table('policy_payments')
            ->whereIn('policy_id', $policyIds)
            ->where('status', PaymentStatus::Paid->value)
            ->pluck('id')
            ->all();

        if ($paidPaymentIds === []) {
            return;
        }

        DB::table('policy_payments')
            ->whereIn('policy_id', $policyIds)
            ->whereNotIn('id', $paidPaymentIds)
            ->whereIn('status', [
                PaymentStatus::Draft->value,
                PaymentStatus::Scheduled->value,
                PaymentStatus::Overdue->value,
            ])
            ->update([
                'status' => PaymentStatus::Canceled->value,
                'updated_at' => now(),
            ]);
    }

    protected function recomputeAllPolicyStatuses(): void
    {
        Policy::query()
            ->orderBy('id')
            ->chunkById(100, function ($policies) {
                foreach ($policies as $policy) {
                    $policy->recomputeStatus();
                }
            });
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