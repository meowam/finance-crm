<?php

namespace Tests\Support;

use App\Models\Claim;
use App\Models\Client;
use App\Models\InsuranceCategory;
use App\Models\InsuranceCompany;
use App\Models\InsuranceOffer;
use App\Models\InsuranceProduct;
use App\Models\LeadRequest;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Carbon;

trait CreatesDomainObjects
{
    protected int $seq = 1;

    protected function nextSeq(): int
    {
        return $this->seq++;
    }

    protected function makeUser(string $role = 'manager', array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeClient(User $manager, array $overrides = []): Client
    {
        $seq = $this->nextSeq();

        return Client::create(array_merge([
            'type' => 'individual',
            'status' => 'lead',
            'first_name' => 'Іван' . $seq,
            'last_name' => 'Петренко' . $seq,
            'middle_name' => 'Іванович',
            'company_name' => null,
            'primary_email' => "client{$seq}@example.com",
            'primary_phone' => '+38067' . str_pad((string) $seq, 7, '0', STR_PAD_LEFT),
            'document_number' => 'AA' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
            'tax_id' => str_pad((string) $seq, 10, '1', STR_PAD_LEFT),
            'date_of_birth' => '1990-01-01',
            'preferred_contact_method' => 'phone',
            'city' => 'Київ',
            'address_line' => 'вул. Хрещатик, 1',
            'source' => 'office',
            'assigned_user_id' => $manager->id,
            'notes' => null,
        ], $overrides));
    }

    protected function makeOffer(array $overrides = []): InsuranceOffer
    {
        $seq = $this->nextSeq();

        $category = InsuranceCategory::create([
            'code' => 'AUTO' . $seq,
            'name' => 'Автострахування ' . $seq,
            'description' => 'Категорія',
        ]);

        $product = InsuranceProduct::create([
            'category_id' => $category->id,
            'code' => 'AUTO-STD-' . $seq,
            'name' => 'Автоцивілка ' . $seq,
            'description' => 'Базовий продукт',
            'sales_enabled' => true,
            'metadata' => [],
        ]);

        $company = InsuranceCompany::create([
            'name' => 'Test Insurance ' . $seq,
            'license_number' => 'LIC-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
            'country' => 'UA',
            'contact_email' => "office{$seq}@insurance.test",
            'contact_phone' => '+38067' . str_pad((string) ($seq + 1000), 7, '0', STR_PAD_LEFT),
            'website' => 'https://example.test',
        ]);

        return InsuranceOffer::create(array_merge([
            'insurance_product_id' => $product->id,
            'insurance_company_id' => $company->id,
            'offer_name' => 'Базовий',
            'price' => 1000,
            'coverage_amount' => 100000,
            'duration_months' => 12,
            'franchise' => 0,
            'benefits' => 'Стандартне покриття',
            'conditions' => [],
        ], $overrides));
    }

    protected function makePolicy(
        User $manager,
        ?Client $client = null,
        ?InsuranceOffer $offer = null,
        array $overrides = [],
    ): Policy {
        $client ??= $this->makeClient($manager);
        $offer ??= $this->makeOffer();

        $effectiveDate = Carbon::now()->addDay();
        $expirationDate = $effectiveDate->copy()->addMonths(8);
        $paymentDueAt = $effectiveDate->copy()->addDays(7);

        $desiredStatus = $overrides['status'] ?? null;

        Policy::$suppressAutoDraft = true;

        try {
            /** @var Policy $policy */
            $policy = Policy::create(array_merge([
                'client_id' => $client->id,
                'insurance_offer_id' => $offer->id,
                'agent_id' => $manager->id,
                'status' => 'draft',
                'effective_date' => $effectiveDate->toDateString(),
                'expiration_date' => $expirationDate->toDateString(),
                'premium_amount' => 1500,
                'coverage_amount' => 100000,
                'payment_frequency' => 'once',
                'commission_rate' => 3,
                'notes' => null,
                'payment_due_at' => $paymentDueAt->toDateString(),
            ], $overrides));

            if ($desiredStatus !== null) {
                $desiredValue = $desiredStatus instanceof \BackedEnum
                    ? $desiredStatus->value
                    : (string) $desiredStatus;

                $currentValue = $policy->status instanceof \BackedEnum
                    ? $policy->status->value
                    : (string) $policy->status;

                if ($currentValue !== $desiredValue) {
                    $policy->forceFill([
                        'status' => $desiredValue,
                    ])->saveQuietly();
                }
            }

            return $policy->refresh();
        } finally {
            Policy::$suppressAutoDraft = false;
        }
    }

    protected function makeLeadRequest(User $manager, array $overrides = []): LeadRequest
    {
        $seq = $this->nextSeq();

        return LeadRequest::create(array_merge([
            'type' => 'individual',
            'first_name' => 'Lead' . $seq,
            'last_name' => 'User' . $seq,
            'middle_name' => null,
            'company_name' => null,
            'phone' => '+38067' . str_pad((string) ($seq + 2000), 7, '0', STR_PAD_LEFT),
            'email' => "lead{$seq}@example.com",
            'interest' => 'Автострахування',
            'source' => 'office',
            'status' => 'new',
            'comment' => 'Test lead',
            'assigned_user_id' => $manager->id,
            'converted_client_id' => null,
        ], $overrides));
    }

    protected function makeClaim(Policy $policy, User $reporter, array $overrides = []): Claim
    {
        return Claim::create(array_merge([
            'policy_id' => $policy->id,
            'reported_by_id' => $reporter->id,
            'status' => 'на розгляді',
            'reported_at' => now(),
            'loss_occurred_at' => now()->subDay(),
            'loss_location' => 'Київ',
            'cause' => 'ДТП',
            'amount_claimed' => 25000,
            'amount_reserve' => 10000,
            'amount_paid' => 0,
            'description' => 'Тестовий страховий випадок',
            'metadata' => [],
        ], $overrides));
    }
}