<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Filament\Resources\Claims\Schemas\ClaimForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\In;
use Tests\TestCase;

class ClaimCreationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_create_status_options_only_include_reviewing(): void
    {
        $this->assertSame([
            ClaimStatus::Reviewing->value => ClaimStatus::Reviewing->label(),
        ], ClaimForm::statusOptionsForOperation('create'));
    }

    public function test_claim_edit_status_options_include_all_claim_statuses(): void
    {
        $this->assertSame(
            ClaimStatus::options(),
            ClaimForm::statusOptionsForOperation('edit')
        );
    }

    public function test_claim_create_status_rules_only_allow_reviewing(): void
    {
        $rules = ClaimForm::statusRulesForOperation('create');

        $this->assertSame('required', $rules[0]);
        $this->assertInstanceOf(In::class, $rules[1]);

        $this->assertTrue($this->statusPasses($rules, ClaimStatus::Reviewing->value));
        $this->assertFalse($this->statusPasses($rules, ClaimStatus::Paid->value));
        $this->assertFalse($this->statusPasses($rules, ClaimStatus::Approved->value));
        $this->assertFalse($this->statusPasses($rules, ClaimStatus::Rejected->value));
    }

    public function test_claim_edit_status_rules_allow_all_claim_statuses(): void
    {
        $rules = ClaimForm::statusRulesForOperation('edit');

        $this->assertSame('required', $rules[0]);
        $this->assertInstanceOf(In::class, $rules[1]);

        foreach (ClaimStatus::values() as $status) {
            $this->assertTrue($this->statusPasses($rules, $status));
        }
    }

    protected function statusPasses(array $rules, string $status): bool
    {
        return Validator::make(
            ['status' => $status],
            ['status' => $rules]
        )->passes();
    }
}