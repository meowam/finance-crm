<?php

namespace Tests\Feature;

use App\Models\PolicyPayment;
use App\Services\Reports\ManagerReportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class ManagerReportsServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

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

    public function test_manager_only_gets_himself_in_filter_options(): void
    {
        $managerA = $this->makeUser('manager', ['name' => 'Manager A']);
        $this->makeUser('manager', ['name' => 'Manager B']);
        $service = app(ManagerReportsService::class);

        $options = $service->getManagersForFilter($managerA);

        $this->assertCount(1, $options);
        $this->assertSame($managerA->id, $options->first()->id);
    }

    public function test_admin_gets_all_managers_in_filter_options(): void
    {
        $admin = $this->makeUser('admin');
        $managerA = $this->makeUser('manager', ['name' => 'Manager A']);
        $managerB = $this->makeUser('manager', ['name' => 'Manager B']);
        $service = app(ManagerReportsService::class);

        $options = $service->getManagersForFilter($admin);

        $this->assertCount(2, $options);
        $this->assertEqualsCanonicalizing(
            [$managerA->id, $managerB->id],
            $options->pluck('id')->all()
        );
    }

    public function test_manager_report_summary_contains_only_his_data(): void
    {
        $managerA = $this->makeUser('manager', ['name' => 'Manager A']);
        $managerB = $this->makeUser('manager', ['name' => 'Manager B']);

        $clientA = $this->makeClient($managerA, ['status' => 'active']);
        $clientB = $this->makeClient($managerB, ['status' => 'lead']);

        $policyA = $this->makePolicy($managerA, $clientA, null, [
            'status' => 'active',
            'premium_amount' => 2000,
        ]);

        $policyB = $this->makePolicy($managerB, $clientB, null, [
            'status' => 'draft',
            'premium_amount' => 3000,
        ]);

        PolicyPayment::create([
            'policy_id' => $policyA->id,
            'due_date' => now()->toDateString(),
            'amount' => 2000,
            'status' => 'paid',
            'method' => 'card',
        ]);

        PolicyPayment::create([
            'policy_id' => $policyB->id,
            'due_date' => now()->toDateString(),
            'amount' => 3000,
            'status' => 'scheduled',
            'method' => 'transfer',
        ]);

        $this->makeClaim($policyA, $managerA, [
            'amount_claimed' => 10000,
            'amount_paid' => 5000,
        ]);

        $this->makeClaim($policyB, $managerB, [
            'amount_claimed' => 15000,
            'amount_paid' => 0,
        ]);

        $service = app(ManagerReportsService::class);

        $rows = $service->getSummaryRows([
            'date_from' => now()->subMonth()->toDateString(),
            'date_until' => now()->toDateString(),
            'manager_id' => null,
            'client_source' => null,
            'policy_status' => null,
        ], $managerA);

        $this->assertCount(1, $rows);
        $this->assertSame($managerA->id, $rows[0]['manager_id']);
        $this->assertSame(1, $rows[0]['new_clients']);
        $this->assertSame(1, $rows[0]['active_clients']);
        $this->assertSame(1, $rows[0]['policies_total']);
        $this->assertSame(1, $rows[0]['payments_paid']);
        $this->assertSame(1, $rows[0]['claims_total']);
        $this->assertSame(10000.0, $rows[0]['claims_amount_claimed']);
        $this->assertSame(5000.0, $rows[0]['claims_amount_paid']);
    }

    public function test_admin_report_summary_can_be_filtered_by_manager(): void
    {
        $admin = $this->makeUser('admin');
        $managerA = $this->makeUser('manager', ['name' => 'Manager A']);
        $managerB = $this->makeUser('manager', ['name' => 'Manager B']);

        $this->makeClient($managerA);
        $this->makeClient($managerB);

        $service = app(ManagerReportsService::class);

        $rows = $service->getSummaryRows([
            'date_from' => now()->subMonth()->toDateString(),
            'date_until' => now()->toDateString(),
            'manager_id' => (string) $managerB->id,
            'client_source' => null,
            'policy_status' => null,
        ], $admin);

        $this->assertCount(1, $rows);
        $this->assertSame($managerB->id, $rows[0]['manager_id']);
    }

    public function test_summary_totals_are_calculated_correctly(): void
    {
        $service = app(ManagerReportsService::class);

        $totals = $service->getSummaryTotals([
            [
                'new_clients' => 1,
                'lead_clients' => 1,
                'active_clients' => 0,
                'policies_total' => 2,
                'policies_active' => 1,
                'premium_sum' => 1000,
                'payments_paid' => 1,
                'payments_scheduled' => 1,
                'payments_overdue' => 0,
                'claims_total' => 1,
                'claims_amount_claimed' => 5000,
                'claims_amount_paid' => 1000,
            ],
            [
                'new_clients' => 2,
                'lead_clients' => 0,
                'active_clients' => 2,
                'policies_total' => 3,
                'policies_active' => 2,
                'premium_sum' => 3000,
                'payments_paid' => 2,
                'payments_scheduled' => 0,
                'payments_overdue' => 1,
                'claims_total' => 2,
                'claims_amount_claimed' => 7000,
                'claims_amount_paid' => 2000,
            ],
        ]);

        $this->assertSame(3, $totals['new_clients']);
        $this->assertSame(1, $totals['lead_clients']);
        $this->assertSame(2, $totals['active_clients']);
        $this->assertSame(5, $totals['policies_total']);
        $this->assertSame(3, $totals['policies_active']);
        $this->assertSame(4000.0, $totals['premium_sum']);
        $this->assertSame(3, $totals['payments_paid']);
        $this->assertSame(1, $totals['payments_scheduled']);
        $this->assertSame(1, $totals['payments_overdue']);
        $this->assertSame(3, $totals['claims_total']);
        $this->assertSame(12000.0, $totals['claims_amount_claimed']);
        $this->assertSame(3000.0, $totals['claims_amount_paid']);
    }
}