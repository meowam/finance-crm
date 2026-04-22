<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\InsuranceCategory;
use App\Models\InsuranceCompany;
use App\Models\InsuranceOffer;
use App\Models\InsuranceProduct;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PolicyPaymentRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_second_active_payment_when_scheduled_payment_already_exists(): void
    {
        $manager = $this->createManager();
        $this->actingAs($manager);

        $policy = $this->createPolicyForManager($manager);

        /** @var PolicyPayment $firstPayment */
        $firstPayment = $policy->payments()->firstOrFail();
        $firstPayment->update([
            'method' => PaymentMethod::Transfer->value,
            'status' => PaymentStatus::Scheduled->value,
        ]);

        $this->expectException(ValidationException::class);

        $policy->payments()->create([
            'amount' => $policy->premium_amount,
            'method' => PaymentMethod::Transfer->value,
            'status' => PaymentStatus::Scheduled->value,
            'due_date' => now()->addDay()->toDateString(),
        ]);
    }

    public function test_cannot_create_second_active_payment_when_paid_payment_already_exists(): void
    {
        $manager = $this->createManager();
        $this->actingAs($manager);

        $policy = $this->createPolicyForManager($manager);

        /** @var PolicyPayment $firstPayment */
        $firstPayment = $policy->payments()->firstOrFail();
        $firstPayment->update([
            'method' => PaymentMethod::Card->value,
            'status' => PaymentStatus::Paid->value,
        ]);

        $this->expectException(ValidationException::class);

        $policy->payments()->create([
            'amount' => $policy->premium_amount,
            'method' => PaymentMethod::Transfer->value,
            'status' => PaymentStatus::Scheduled->value,
            'due_date' => now()->addDay()->toDateString(),
        ]);
    }

    public function test_can_create_new_active_payment_after_previous_payment_was_canceled(): void
    {
        $manager = $this->createManager();
        $this->actingAs($manager);

        $policy = $this->createPolicyForManager($manager);

        /** @var PolicyPayment $firstPayment */
        $firstPayment = $policy->payments()->firstOrFail();
        $firstPayment->update([
            'method' => PaymentMethod::Transfer->value,
            'status' => PaymentStatus::Canceled->value,
        ]);

        $secondPayment = $policy->payments()->create([
            'amount' => $policy->premium_amount,
            'method' => PaymentMethod::Transfer->value,
            'status' => PaymentStatus::Scheduled->value,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertDatabaseHas('policy_payments', [
            'id' => $secondPayment->id,
            'policy_id' => $policy->id,
            'status' => PaymentStatus::Scheduled->value,
            'method' => PaymentMethod::Transfer->value,
        ]);

        $this->assertSame(2, $policy->payments()->count());
        $this->assertSame(
            1,
            $policy->payments()->whereIn('status', [
                PaymentStatus::Paid->value,
                PaymentStatus::Scheduled->value,
            ])->count()
        );
    }

    protected function createManager(): User
    {
        return User::factory()->create([
            'role' => 'manager',
            'is_active' => true,
        ]);
    }

    protected function createPolicyForManager(User $manager): Policy
    {
        $client = Client::query()->create([
            'type' => 'individual',
            'status' => 'active',
            'first_name' => 'Іван',
            'last_name' => 'Петренко',
            'middle_name' => 'Іванович',
            'primary_email' => 'client@example.test',
            'primary_phone' => '+380671234567',
            'document_number' => 'AA123456',
            'tax_id' => '1234567890',
            'date_of_birth' => '1995-01-10',
            'source' => 'office',
            'assigned_user_id' => $manager->id,
        ]);

        $category = InsuranceCategory::query()->create([
            'code' => 'AUTO',
            'name' => 'Автострахування',
            'description' => 'Тестова категорія',
        ]);

        $product = InsuranceProduct::query()->create([
            'category_id' => $category->id,
            'code' => 'AUTO-STD',
            'name' => 'Авто стандарт',
            'description' => 'Тестовий продукт',
            'sales_enabled' => true,
            'metadata' => [],
        ]);

        $company = InsuranceCompany::query()->create([
            'name' => 'Test Insurance',
            'license_number' => 'LIC-001',
            'country' => 'UA',
            'contact_email' => 'ins@example.test',
            'contact_phone' => '+380501112233',
            'website' => 'https://example.test',
        ]);

        $offer = InsuranceOffer::query()->create([
            'insurance_product_id' => $product->id,
            'insurance_company_id' => $company->id,
            'offer_name' => 'Комфорт+',
            'price' => 1000,
            'coverage_amount' => 50000,
            'duration_months' => 12,
            'franchise' => 0,
            'benefits' => 'Тестові переваги',
            'conditions' => [],
        ]);

        return Policy::query()->create([
            'client_id' => $client->id,
            'insurance_offer_id' => $offer->id,
            'agent_id' => $manager->id,
            'status' => 'draft',
            'effective_date' => now()->toDateString(),
            'expiration_date' => now()->addMonths(12)->toDateString(),
            'premium_amount' => 1015.00,
            'coverage_amount' => 50000.00,
            'payment_frequency' => 'once',
            'commission_rate' => 1.50,
            'notes' => 'Тестовий поліс',
        ]);
    }
}