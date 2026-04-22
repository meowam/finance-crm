<?php

namespace Tests\Unit;

use App\Filament\Resources\PolicyPayments\Schemas\PolicyPaymentForm;
use App\Models\InsuranceOffer;
use App\Models\Policy;
use ReflectionMethod;
use Tests\TestCase;

class PolicyPaymentFormTest extends TestCase
{
    public function test_resolve_amount_prefers_policy_premium_amount_over_current_offer_values(): void
    {
        $offer = new InsuranceOffer([
            'offer_name' => 'Комфорт+',
            'price' => 9999.00,
            'coverage_amount' => 100000.00,
            'duration_months' => 24,
            'franchise' => 0,
            'benefits' => 'Тест',
            'conditions' => [],
        ]);

        $policy = new Policy([
            'premium_amount' => 1015.00,
            'commission_rate' => 1.50,
        ]);

        $policy->setRelation('insuranceOffer', $offer);

        $method = new ReflectionMethod(PolicyPaymentForm::class, 'resolveAmount');
        $method->setAccessible(true);

        $resolvedAmount = $method->invoke(null, $policy);

        $this->assertSame('1015.00', $resolvedAmount);
    }
}