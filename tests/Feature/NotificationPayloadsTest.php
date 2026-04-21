<?php

namespace Tests\Feature;

use App\Models\PolicyPayment;
use App\Notifications\OverduePaymentNotification;
use App\Notifications\PolicyExpiringSoonNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class NotificationPayloadsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_overdue_payment_notification_builds_expected_database_payload(): void
    {
        $manager = $this->makeUser('manager');
        $admin = $this->makeUser('admin');

        $client = $this->makeClient($manager, [
            'first_name' => 'Anastasiia',
            'last_name' => 'Khomenko',
        ]);

        $policy = $this->makePolicy($manager, $client, null, [
            'policy_number' => 'POL-TEST-001',
        ]);

        $payment = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => '2026-04-20',
            'amount' => 1500,
            'status' => 'overdue',
            'method' => 'transfer',
        ]);

        $payload = (new OverduePaymentNotification($payment->fresh('policy.client')))->toDatabase($admin);

        $this->assertSame('payment_overdue', $payload['notification_type']);
        $this->assertSame($payment->id, $payload['payment_id']);
        $this->assertSame($policy->id, $payload['policy_id']);
        $this->assertSame('POL-TEST-001', $payload['policy_number']);
        $this->assertSame($client->id, $payload['client_id']);
        $this->assertStringContainsString('Khomenko Anastasiia', $payload['body']);
        $this->assertStringContainsString('POL-TEST-001', $payload['title']);
    }

    public function test_expiring_policy_notification_builds_expected_database_payload(): void
    {
        $manager = $this->makeUser('manager');
        $supervisor = $this->makeUser('supervisor');

        $client = $this->makeClient($manager, [
            'first_name' => 'Olena',
            'last_name' => 'Pavlenko',
        ]);

        $policy = $this->makePolicy($manager, $client, null, [
            'policy_number' => 'POL-EXP-001',
            'expiration_date' => now()->addDays(14)->toDateString(),
        ]);

        $payload = (new PolicyExpiringSoonNotification($policy->fresh('client'), 14))->toDatabase($supervisor);

        $this->assertSame('policy_expiring', $payload['notification_type']);
        $this->assertSame($policy->id, $payload['policy_id']);
        $this->assertSame('POL-EXP-001', $payload['policy_number']);
        $this->assertSame($client->id, $payload['client_id']);
        $this->assertSame(14, $payload['days_left']);
        $this->assertStringContainsString('POL-EXP-001', $payload['title']);
        $this->assertStringContainsString('Pavlenko Olena', $payload['body']);
    }
}