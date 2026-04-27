<?php

namespace Tests\Feature;

use App\Enums\PolicyStatus;
use App\Filament\Resources\Claims\Pages\CreateClaim;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class ClaimValidationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    protected function makeCreateClaimPage(): object
    {
        return new class extends CreateClaim {
            public function validatePayload(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };
    }

    protected function makeClaimablePolicy(
        User $manager,
        string $status = PolicyStatus::Active->value,
        array $overrides = [],
    ): Policy {
        $effectiveDate = $overrides['effective_date'] ?? now()->subDays(30)->toDateString();
        $expirationDate = $overrides['expiration_date'] ?? now()->addDays(30)->toDateString();
        $coverageAmount = $overrides['coverage_amount'] ?? 100000;

        unset(
            $overrides['effective_date'],
            $overrides['expiration_date'],
            $overrides['coverage_amount'],
        );

        $policy = $this->makePolicy($manager, null, null, array_merge([
            'status' => $status,
            'effective_date' => $effectiveDate,
            'expiration_date' => $expirationDate,
            'payment_due_at' => now()->addDays(7)->toDateString(),
            'coverage_amount' => $coverageAmount,
        ], $overrides));

        $policy->forceFill([
            'status' => $status,
            'effective_date' => $effectiveDate,
            'expiration_date' => $expirationDate,
            'coverage_amount' => $coverageAmount,
        ])->saveQuietly();

        return $policy->refresh();
    }

    protected function validClaimData(Policy $policy, array $overrides = []): array
    {
        return array_merge([
            'policy_id' => $policy->id,
            'status' => 'на розгляді',
            'loss_occurred_at' => now()->subDay()->toDateString(),
            'loss_location' => 'Київ',
            'cause' => 'ДТП',
            'amount_claimed' => '25000.00',
            'amount_reserve' => '10000.00',
            'amount_paid' => '0.00',
            'description' => 'Тестовий страховий випадок',
            'metadata' => [],
            'notes' => [],
        ], $overrides);
    }

    protected function assertPayloadFailsForField(array $data, string $field): void
    {
        try {
            $this->makeCreateClaimPage()->validatePayload($data);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());

            return;
        }

        $this->fail("Expected validation error for [{$field}], but validation passed.");
    }

    public function test_claim_can_be_created_for_active_policy_when_loss_date_is_inside_policy_period(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makeClaimablePolicy($manager, PolicyStatus::Active->value);

        $this->actingAs($manager);

        $data = $this->makeCreateClaimPage()->validatePayload(
            $this->validClaimData($policy)
        );

        $this->assertSame($manager->id, $data['reported_by_id']);
        $this->assertSame($policy->id, $data['policy_id']);
    }

    public function test_claim_can_be_created_for_completed_policy_when_loss_date_is_inside_policy_period(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makeClaimablePolicy($manager, PolicyStatus::Completed->value, [
            'effective_date' => now()->subDays(90)->toDateString(),
            'expiration_date' => now()->subDays(10)->toDateString(),
        ]);

        $this->actingAs($manager);

        $data = $this->makeCreateClaimPage()->validatePayload(
            $this->validClaimData($policy, [
                'loss_occurred_at' => now()->subDays(20)->toDateString(),
            ])
        );

        $this->assertSame($manager->id, $data['reported_by_id']);
        $this->assertSame($policy->id, $data['policy_id']);
    }

    public function test_claim_cannot_be_created_for_canceled_policy(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makeClaimablePolicy($manager, PolicyStatus::Canceled->value);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy),
            'policy_id'
        );
    }

    public function test_claim_cannot_be_created_for_draft_policy(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makeClaimablePolicy($manager, PolicyStatus::Draft->value);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy),
            'policy_id'
        );
    }

    public function test_claim_loss_date_must_not_be_before_policy_start_date(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makeClaimablePolicy($manager, PolicyStatus::Active->value, [
            'effective_date' => now()->subDays(10)->toDateString(),
            'expiration_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy, [
                'loss_occurred_at' => now()->subDays(11)->toDateString(),
            ]),
            'loss_occurred_at'
        );
    }

    public function test_claim_loss_date_must_not_be_after_policy_expiration_date(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makeClaimablePolicy($manager, PolicyStatus::Completed->value, [
            'effective_date' => now()->subDays(90)->toDateString(),
            'expiration_date' => now()->subDays(10)->toDateString(),
        ]);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy, [
                'loss_occurred_at' => now()->subDays(9)->toDateString(),
            ]),
            'loss_occurred_at'
        );
    }

    public function test_claimed_amount_must_be_greater_than_zero(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makeClaimablePolicy($manager);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy, [
                'amount_claimed' => '0.00',
            ]),
            'amount_claimed'
        );
    }

    public function test_claimed_amount_must_not_exceed_policy_coverage(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makeClaimablePolicy($manager, PolicyStatus::Active->value, [
            'coverage_amount' => 100000,
        ]);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy, [
                'amount_claimed' => '100000.01',
            ]),
            'amount_claimed'
        );
    }

    public function test_reserve_amount_must_not_exceed_policy_coverage(): void
    {
        $manager = $this->makeUser('manager');

        $policy = $this->makeClaimablePolicy($manager, PolicyStatus::Active->value, [
            'coverage_amount' => 100000,
        ]);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy, [
                'amount_reserve' => '100000.01',
            ]),
            'amount_reserve'
        );
    }

    public function test_paid_amount_must_not_exceed_reserve_amount(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makeClaimablePolicy($manager);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy, [
                'amount_reserve' => '10000.00',
                'amount_paid' => '10000.01',
            ]),
            'amount_paid'
        );
    }

    public function test_paid_claim_requires_positive_paid_amount(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makeClaimablePolicy($manager);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy, [
                'status' => 'виплачено',
                'amount_paid' => '0.00',
            ]),
            'amount_paid'
        );
    }

    public function test_rejected_claim_must_have_zero_paid_amount(): void
    {
        $manager = $this->makeUser('manager');
        $policy = $this->makeClaimablePolicy($manager);

        $this->actingAs($manager);

        $this->assertPayloadFailsForField(
            $this->validClaimData($policy, [
                'status' => 'відхилено',
                'amount_paid' => '1.00',
            ]),
            'amount_paid'
        );
    }

    public function test_manager_cannot_create_claim_for_another_managers_policy(): void
    {
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');

        $policy = $this->makeClaimablePolicy($managerA);

        $this->actingAs($managerB);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->makeCreateClaimPage()->validatePayload(
            $this->validClaimData($policy)
        );
    }
}