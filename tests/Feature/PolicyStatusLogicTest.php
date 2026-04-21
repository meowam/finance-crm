<?php

namespace Tests\Feature;

use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\InsuranceCategory;
use App\Models\InsuranceCompany;
use App\Models\InsuranceOffer;
use App\Models\InsuranceProduct;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PolicyStatusLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 4, 21, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function makeManager(): User
    {
        return User::factory()->create([
            'role' => 'manager',
            'is_active' => true,
        ]);
    }

    protected function makeClient(User $manager): Client
    {
        return Client::create([
            'type' => 'individual',
            'status' => 'lead',
            'first_name' => 'Іван',
            'last_name' => 'Петренко',
            'middle_name' => 'Іванович',
            'primary_email' => 'ivan@example.com',
            'primary_phone' => '+380671234567',
            'document_number' => 'AA123456',
            'tax_id' => '1234567890',
            'date_of_birth' => '1990-01-01',
            'preferred_contact_method' => 'phone',
            'city' => 'Київ',
            'address_line' => 'вул. Хрещатик, 1',
            'source' => 'online',
            'assigned_user_id' => $manager->id,
        ]);
    }

    protected function makeOffer(): InsuranceOffer
    {
        $category = InsuranceCategory::create([
            'code' => 'AUTO',
            'name' => 'Автострахування',
            'description' => 'Категорія автострахування',
        ]);

        $product = InsuranceProduct::create([
            'category_id' => $category->id,
            'code' => 'AUTO-STD',
            'name' => 'Автоцивілка',
            'description' => 'Базовий продукт',
            'sales_enabled' => true,
            'metadata' => [],
        ]);

        $company = InsuranceCompany::create([
            'name' => 'Test Insurance',
            'license_number' => 'LIC-123456',
            'country' => 'UA',
            'contact_email' => 'office@test-insurance.local',
            'contact_phone' => '+380671111111',
            'website' => 'https://example.test',
        ]);

        return InsuranceOffer::create([
            'insurance_product_id' => $product->id,
            'insurance_company_id' => $company->id,
            'offer_name' => 'Базовий',
            'price' => 1000,
            'coverage_amount' => 100000,
            'duration_months' => 12,
            'franchise' => 0,
            'benefits' => 'Стандартне покриття',
            'conditions' => [],
        ]);
    }

    protected function makePolicy(array $overrides = []): Policy
    {
        $manager = $overrides['manager'] ?? $this->makeManager();
        $client = $overrides['client'] ?? $this->makeClient($manager);
        $offer = $overrides['offer'] ?? $this->makeOffer();

        Policy::$suppressAutoDraft = true;

        try {
            return Policy::create([
                'client_id' => $client->id,
                'insurance_offer_id' => $offer->id,
                'agent_id' => $manager->id,
                'status' => $overrides['status'] ?? PolicyStatus::Draft->value,
                'effective_date' => $overrides['effective_date'] ?? '2026-04-10',
                'expiration_date' => $overrides['expiration_date'] ?? '2026-12-31',
                'premium_amount' => $overrides['premium_amount'] ?? 1500,
                'coverage_amount' => $overrides['coverage_amount'] ?? 100000,
                'payment_frequency' => $overrides['payment_frequency'] ?? 'once',
                'commission_rate' => $overrides['commission_rate'] ?? 3,
                'notes' => $overrides['notes'] ?? null,
                'payment_due_at' => $overrides['payment_due_at'] ?? '2026-04-17',
            ]);
        } finally {
            Policy::$suppressAutoDraft = false;
        }
    }

    public function test_policy_without_paid_payment_and_before_due_date_stays_draft(): void
    {
        $policy = $this->makePolicy([
            'payment_due_at' => '2026-04-25',
            'status' => PolicyStatus::Active->value,
        ]);

        $policy->recomputeStatus();

        $this->assertEquals(PolicyStatus::Draft, $policy->fresh()->status);
    }

    public function test_policy_with_paid_payment_becomes_active(): void
    {
        $policy = $this->makePolicy([
            'payment_due_at' => '2026-04-25',
            'status' => PolicyStatus::Draft->value,
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => '2026-04-17',
            'amount' => 1500,
            'status' => 'paid',
            'method' => 'card',
        ]);

        $policy->refresh()->recomputeStatus();

        $this->assertEquals(PolicyStatus::Active, $policy->fresh()->status);
    }

    public function test_policy_without_paid_payment_and_after_due_date_becomes_canceled(): void
    {
        $policy = $this->makePolicy([
            'payment_due_at' => '2026-04-15',
            'status' => PolicyStatus::Draft->value,
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => '2026-04-15',
            'amount' => 1500,
            'status' => 'overdue',
            'method' => 'transfer',
        ]);

        $policy->refresh()->recomputeStatus();

        $this->assertEquals(PolicyStatus::Canceled, $policy->fresh()->status);
    }

    public function test_paid_policy_with_expired_end_date_becomes_completed(): void
    {
        $policy = $this->makePolicy([
            'effective_date' => '2025-04-01',
            'expiration_date' => '2026-04-20',
            'payment_due_at' => '2025-04-08',
            'status' => PolicyStatus::Active->value,
        ]);

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => '2025-04-08',
            'amount' => 1500,
            'status' => 'paid',
            'method' => 'card',
        ]);

        $policy->refresh()->recomputeStatus();

        $this->assertEquals(PolicyStatus::Completed, $policy->fresh()->status);
    }

    public function test_removing_paid_payment_recomputes_status_back_to_canceled_if_due_date_passed(): void
    {
        $policy = $this->makePolicy([
            'payment_due_at' => '2026-04-15',
            'status' => PolicyStatus::Draft->value,
        ]);

        $payment = PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => '2026-04-15',
            'amount' => 1500,
            'status' => 'paid',
            'method' => 'cash',
        ]);

        $this->assertEquals(PolicyStatus::Active, $policy->fresh()->status);

        $payment->delete();

        $this->assertEquals(PolicyStatus::Canceled, $policy->fresh()->status);
    }
}