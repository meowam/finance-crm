<?php

namespace App\Services\Policies;

use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use Illuminate\Support\Facades\DB;

class PolicyDailyService
{
    public function run(float $rate): void
    {
        DB::transaction(function () use ($rate) {
            $this->markOverdueTransfer();
            $this->markOverdueNoMethod();
            $this->cancelPoliciesWithoutPaid();
            $this->randomizeScheduledToPaid($rate);
            $this->activateOrCompletePaidPolicies();
        }, 3);
    }

    protected function markOverdueTransfer(): void
    {
        DB::table('policy_payments')
            ->where('method', 'transfer')
            ->where('status', 'scheduled')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue', 'updated_at' => now()]);
    }

    protected function markOverdueNoMethod(): void
    {
        DB::table('policy_payments as pp')
            ->join('policies as p', 'p.id', '=', 'pp.policy_id')
            ->where('pp.method', 'no_method')
            ->where('pp.status', 'draft')
            ->whereDate('p.payment_due_at', '<', now()->toDateString())
            ->update(['pp.status' => 'overdue', 'pp.updated_at' => now()]);
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
            ->update(['p.status' => 'canceled', 'p.updated_at' => now()]);
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
            ->update(['status' => 'paid', 'paid_at' => now(), 'updated_at' => now()]);
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

        if (!empty($toComplete)) {
            DB::table('policies')
                ->whereIn('id', $toComplete)
                ->update(['status' => PolicyStatus::Completed->value, 'updated_at' => now()]);
        }

        $toActivate = Policy::query()
            ->whereIn('id', $policyIds)
            ->whereNotIn('id', $toComplete)
            ->where('status', '!=', PolicyStatus::Canceled->value)
            ->pluck('id')
            ->all();

        if (!empty($toActivate)) {
            DB::table('policies')
                ->whereIn('id', $toActivate)
                ->update(['status' => PolicyStatus::Active->value, 'updated_at' => now()]);
        }
    }
}
