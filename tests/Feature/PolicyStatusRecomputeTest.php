<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use App\Models\PolicyPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class PolicyStatusRecomputeTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_policy_becomes_active_when_it_has_paid_payment_and_is_not_expired(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makePolicy($manager, overrides: [
            'status' => PolicyStatus::Draft->value,
            'effective_date' => now()->subDay()->toDateString(),
            'expiration_date' => now()->addMonth()->toDateString(),
            'payment_due_at' => now()->addDays(7)->toDateString(),
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->toDateString(),
            'amount' => 1500,
            'method' => 'cash',
            'status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
        ]);

        $policy->refresh()->recomputeStatus();

        $this->assertSame(
            PolicyStatus::Active->value,
            $policy->refresh()->status->value
        );
    }

    public function test_policy_becomes_completed_when_it_has_paid_payment_and_is_expired(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makePolicy($manager, overrides: [
            'status' => PolicyStatus::Draft->value,
            'effective_date' => now()->subMonths(2)->toDateString(),
            'expiration_date' => now()->subDay()->toDateString(),
            'payment_due_at' => now()->subMonth()->toDateString(),
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->subMonth()->toDateString(),
            'amount' => 1500,
            'method' => 'cash',
            'status' => PaymentStatus::Paid->value,
            'paid_at' => now()->subMonth(),
        ]);

        $policy->refresh()->recomputeStatus();

        $this->assertSame(
            PolicyStatus::Completed->value,
            $policy->refresh()->status->value
        );
    }

    public function test_policy_becomes_canceled_when_payment_due_date_is_past_and_there_is_no_paid_payment(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makePolicy($manager, overrides: [
            'status' => PolicyStatus::Draft->value,
            'effective_date' => now()->subDays(10)->toDateString(),
            'expiration_date' => now()->addMonth()->toDateString(),
            'payment_due_at' => now()->subDay()->toDateString(),
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->subDay()->toDateString(),
            'amount' => 1500,
            'method' => 'no_method',
            'status' => PaymentStatus::Draft->value,
        ]);

        $policy->refresh()->recomputeStatus();

        $this->assertSame(
            PolicyStatus::Canceled->value,
            $policy->refresh()->status->value
        );
    }

    public function test_policy_remains_draft_when_there_is_no_paid_payment_and_payment_is_not_overdue(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makePolicy($manager, overrides: [
            'status' => PolicyStatus::Draft->value,
            'effective_date' => now()->addDay()->toDateString(),
            'expiration_date' => now()->addYear()->toDateString(),
            'payment_due_at' => now()->addDays(7)->toDateString(),
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => 1500,
            'method' => 'no_method',
            'status' => PaymentStatus::Draft->value,
        ]);

        $policy->refresh()->recomputeStatus();

        $this->assertSame(
            PolicyStatus::Draft->value,
            $policy->refresh()->status->value
        );
    }

    public function test_canceled_policy_is_not_recomputed_back_to_active(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makePolicy($manager, overrides: [
            'status' => PolicyStatus::Canceled->value,
            'effective_date' => now()->subDay()->toDateString(),
            'expiration_date' => now()->addMonth()->toDateString(),
            'payment_due_at' => now()->addDays(7)->toDateString(),
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->toDateString(),
            'amount' => 1500,
            'method' => 'cash',
            'status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
        ]);

        $policy->refresh()->recomputeStatus();

        $this->assertSame(
            PolicyStatus::Canceled->value,
            $policy->refresh()->status->value
        );
    }
}