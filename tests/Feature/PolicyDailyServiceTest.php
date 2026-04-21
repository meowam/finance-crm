<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use App\Models\PolicyPayment;
use App\Models\User;
use App\Notifications\OverduePaymentNotification;
use App\Services\Policies\PolicyDailyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class PolicyDailyServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 4, 21, 0, 5, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_daily_service_marks_scheduled_transfer_as_overdue_and_sends_notifications(): void
    {
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor');
        $manager = $this->makeUser('manager');

        $policy = $this->makePolicy($manager, overrides: [
            'payment_due_at' => '2026-04-15',
            'status' => PolicyStatus::Draft->value,
        ]);

        $payment = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => '2026-04-20',
            'amount' => 1500,
            'status' => 'scheduled',
            'method' => 'transfer',
        ]);

        app(PolicyDailyService::class)->run(0);

        $this->assertEquals(PaymentStatus::Overdue, $payment->fresh()->status);
        $this->assertEquals(PolicyStatus::Canceled, $policy->fresh()->status);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'type' => OverduePaymentNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $supervisor->id,
            'type' => OverduePaymentNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $manager->id,
            'type' => OverduePaymentNotification::class,
        ]);
    }

    public function test_daily_service_marks_no_method_draft_payment_as_overdue(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makePolicy($manager, overrides: [
            'payment_due_at' => '2026-04-19',
            'status' => PolicyStatus::Draft->value,
        ]);

        $payment = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => '2026-04-19',
            'amount' => 1500,
            'status' => 'draft',
            'method' => 'no_method',
        ]);

        app(PolicyDailyService::class)->run(0);

        $this->assertEquals(PaymentStatus::Overdue, $payment->fresh()->status);
        $this->assertEquals(PolicyStatus::Canceled, $policy->fresh()->status);
    }

    public function test_daily_service_does_not_duplicate_overdue_notifications_on_second_run(): void
    {
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor');
        $manager = $this->makeUser('manager');

        $policy = $this->makePolicy($manager, overrides: [
            'payment_due_at' => '2026-04-15',
            'status' => PolicyStatus::Draft->value,
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => '2026-04-20',
            'amount' => 1500,
            'status' => 'scheduled',
            'method' => 'transfer',
        ]);

        $service = app(PolicyDailyService::class);

        $service->run(0);
        $service->run(0);

        $this->assertEquals(1, $admin->fresh()->notifications()->where('type', OverduePaymentNotification::class)->count());
        $this->assertEquals(1, $supervisor->fresh()->notifications()->where('type', OverduePaymentNotification::class)->count());
        $this->assertEquals(1, $manager->fresh()->notifications()->where('type', OverduePaymentNotification::class)->count());
    }
}