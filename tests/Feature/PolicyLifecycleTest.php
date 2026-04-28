<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\PolicyPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class PolicyLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_policy_created_without_manual_payment_creates_single_draft_payment(): void
    {
        $manager = $this->makeUser('manager');
        $client = $this->makeClient($manager);
        $offer = $this->makeOffer();

        $effectiveDate = Carbon::now()->addDay();
        $expirationDate = $effectiveDate->copy()->addMonths(12);
        $paymentDueAt = $effectiveDate->copy()->addDays(7);

        $policy = Policy::create([
            'client_id' => $client->id,
            'insurance_offer_id' => $offer->id,
            'agent_id' => $manager->id,
            'status' => PolicyStatus::Draft->value,
            'effective_date' => $effectiveDate->toDateString(),
            'expiration_date' => $expirationDate->toDateString(),
            'premium_amount' => 1500,
            'coverage_amount' => 100000,
            'payment_frequency' => 'once',
            'commission_rate' => 3,
            'notes' => null,
            'payment_due_at' => $paymentDueAt->toDateString(),
        ]);

        $policy->refresh();

        $this->assertNotEmpty($policy->policy_number);
        $this->assertSame(1, $policy->payments()->count());

        $payment = $policy->payments()->first();

        $this->assertInstanceOf(PolicyPayment::class, $payment);
        $this->assertSame(PaymentMethod::NoMethod, $payment->method);
        $this->assertSame(PaymentStatus::Draft, $payment->status);
        $this->assertSame('1500.00', $payment->amount);
        $this->assertSame($paymentDueAt->toDateString(), $payment->due_date->toDateString());
        $this->assertSame(PolicyStatus::Draft, $policy->status);
    }

    public function test_paid_payment_cancels_existing_unfinished_draft_payment_and_activates_policy(): void
    {
        $manager = $this->makeUser('manager');
        $client = $this->makeClient($manager);
        $offer = $this->makeOffer();

        $effectiveDate = Carbon::now()->subDay();
        $expirationDate = Carbon::now()->addYear();
        $paymentDueAt = Carbon::now()->addDays(7);

        $policy = Policy::create([
            'client_id' => $client->id,
            'insurance_offer_id' => $offer->id,
            'agent_id' => $manager->id,
            'status' => PolicyStatus::Draft->value,
            'effective_date' => $effectiveDate->toDateString(),
            'expiration_date' => $expirationDate->toDateString(),
            'premium_amount' => 1500,
            'coverage_amount' => 100000,
            'payment_frequency' => 'once',
            'commission_rate' => 3,
            'notes' => null,
            'payment_due_at' => $paymentDueAt->toDateString(),
        ]);

        $initialDraftPayment = $policy->payments()->first();

        $this->assertInstanceOf(PolicyPayment::class, $initialDraftPayment);
        $this->assertSame(PaymentStatus::Draft, $initialDraftPayment->status);

        $paidPayment = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => $paymentDueAt->toDateString(),
            'amount' => $policy->premium_amount,
            'method' => PaymentMethod::Card->value,
            'status' => PaymentStatus::Paid->value,
            'transaction_reference' => null,
            'notes' => null,
        ]);

        $initialDraftPayment->refresh();
        $paidPayment->refresh();
        $policy->refresh();
        $client->refresh();

        $this->assertSame(PaymentStatus::Canceled, $initialDraftPayment->status);
        $this->assertSame(PaymentStatus::Paid, $paidPayment->status);
        $this->assertNotNull($paidPayment->paid_at);

        $this->assertSame(PolicyStatus::Active, $policy->status);
        $this->assertSame('active', $client->status);
    }

    public function test_new_draft_payment_is_auto_canceled_when_policy_already_has_paid_payment(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makePolicy($manager, overrides: [
            'effective_date' => now()->subDay()->toDateString(),
            'expiration_date' => now()->addYear()->toDateString(),
            'payment_due_at' => now()->addDays(7)->toDateString(),
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => $policy->premium_amount,
            'method' => PaymentMethod::Cash->value,
            'status' => PaymentStatus::Paid->value,
            'transaction_reference' => null,
            'notes' => null,
        ]);

        $draftPayment = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => $policy->premium_amount,
            'method' => PaymentMethod::NoMethod->value,
            'status' => PaymentStatus::Draft->value,
            'transaction_reference' => null,
            'notes' => 'Помилково створена чернетка після оплати.',
        ]);

        $draftPayment->refresh();

        $this->assertSame(PaymentStatus::Canceled, $draftPayment->status);
    }

    public function test_policy_status_does_not_auto_restore_after_cancellation(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makePolicy($manager, overrides: [
            'status' => PolicyStatus::Canceled->value,
            'effective_date' => now()->subDay()->toDateString(),
            'expiration_date' => now()->addYear()->toDateString(),
            'payment_due_at' => now()->addDays(7)->toDateString(),
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => $policy->premium_amount,
            'method' => PaymentMethod::Cash->value,
            'status' => PaymentStatus::Paid->value,
            'transaction_reference' => null,
            'notes' => null,
        ]);

        $policy->refresh()->recomputeStatus();
        $policy->refresh();

        $this->assertSame(PolicyStatus::Canceled, $policy->status);
    }
}