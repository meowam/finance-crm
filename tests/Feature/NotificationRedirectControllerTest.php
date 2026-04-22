<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\InsuranceCategory;
use App\Models\InsuranceCompany;
use App\Models\InsuranceOffer;
use App\Models\InsuranceProduct;
use App\Models\Policy;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationRedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_policy_redirects_using_policy_id_and_marks_notification_as_read(): void
    {
        $manager = $this->createManager();
        $policy = $this->createPolicyForManager($manager);

        $notification = UserNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => \App\Notifications\PolicyExpiringSoonNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $manager->id,
            'data' => [
                'notification_type' => 'policy_expiring',
                'policy_id' => $policy->id,
                'policy_number' => $policy->policy_number,
            ],
            'read_at' => null,
        ]);

        $response = $this
            ->actingAs($manager)
            ->get(route('notifications.open-policy', $notification));

        $response->assertRedirect("/admin/policies/{$policy->id}/edit");

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_open_policy_rejects_unsafe_legacy_external_url(): void
    {
        $manager = $this->createManager();

        $notification = UserNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => \App\Notifications\OverduePaymentNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $manager->id,
            'data' => [
                'notification_type' => 'payment_overdue',
                'policy_url' => 'https://evil.example/phishing',
            ],
            'read_at' => null,
        ]);

        $response = $this
            ->actingAs($manager)
            ->get(route('notifications.open-policy', $notification));

        $response->assertNotFound();
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