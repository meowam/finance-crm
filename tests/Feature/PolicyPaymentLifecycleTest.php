<?php

namespace Tests\Feature;

use App\Filament\Resources\PolicyPayments\Schemas\PolicyPaymentForm;
use App\Models\PolicyPayment;
use App\Services\Policies\PolicyDailyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class PolicyPaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_paid_transfer_cancels_other_draft_and_scheduled_payments_for_same_policy_but_keeps_overdue_final(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makePolicy($manager);

        $scheduled = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->toDateString(),
            'amount' => 1500,
            'method' => 'transfer',
            'status' => 'scheduled',
        ]);

        $draft = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->addDays(3)->toDateString(),
            'amount' => 1500,
            'method' => 'no_method',
            'status' => 'draft',
        ]);

        $overdue = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->subDay()->toDateString(),
            'amount' => 1500,
            'method' => 'no_method',
            'status' => 'overdue',
        ]);

        app(PolicyDailyService::class)->run(1.0);

        $this->assertSame('paid', $scheduled->refresh()->status->value);
        $this->assertSame('canceled', $draft->refresh()->status->value);
        $this->assertSame('overdue', $overdue->refresh()->status->value);
    }

    public function test_canceled_payment_is_locked_in_payment_form(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makePolicy($manager);

        $payment = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->toDateString(),
            'amount' => 1500,
            'method' => 'no_method',
            'status' => 'canceled',
        ]);

        $method = new ReflectionMethod(PolicyPaymentForm::class, 'isLockedStatus');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null, $payment));
    }

    public function test_overdue_payment_is_locked_in_payment_form(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makePolicy($manager);

        $payment = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->subDay()->toDateString(),
            'amount' => 1500,
            'method' => 'no_method',
            'status' => 'overdue',
        ]);

        $method = new ReflectionMethod(PolicyPaymentForm::class, 'isLockedStatus');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null, $payment));
    }
}