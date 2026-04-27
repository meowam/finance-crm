<?php

namespace Tests\Feature;

use App\Filament\Widgets\ClientsPoliciesTrendChart;
use App\Filament\Widgets\ManagerPoliciesChart;
use App\Filament\Widgets\OverviewStats;
use App\Filament\Widgets\PolicyStatusChart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_overview_stats_for_manager_are_scoped_to_his_own_data(): void
    {
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');

        $clientA = $this->makeClient($managerA);
        $clientB = $this->makeClient($managerB);

        $policyA = $this->makePolicy($managerA, $clientA, null, ['status' => 'active']);
        $policyB = $this->makePolicy($managerB, $clientB, null, ['status' => 'active']);

        $this->makeClaim($policyA, $managerA);
        $this->makeClaim($policyB, $managerB);

        $this->actingAs($managerA);

        $widget = new class extends OverviewStats
        {
            public function exposeStats(): array
            {
                return $this->getStats();
            }
        };

        $stats = $widget->exposeStats();

        $this->assertTrue(OverviewStats::canView());

        $this->assertCount(4, $stats);
        $this->assertSame('1', $stats[0]->getValue());
        $this->assertSame('1', $stats[1]->getValue());
        $this->assertSame('1', $stats[2]->getValue());
    }

    public function test_overview_stats_is_visible_only_to_manager(): void
    {
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor');
        $manager = $this->makeUser('manager');

        $this->actingAs($admin);
        $this->assertFalse(OverviewStats::canView());

        $this->actingAs($supervisor);
        $this->assertFalse(OverviewStats::canView());

        $this->actingAs($manager);
        $this->assertTrue(OverviewStats::canView());
    }

    public function test_clients_policies_trend_chart_returns_two_datasets_for_last_six_months(): void
    {
        $manager = $this->makeUser('manager');

        $client = $this->makeClient($manager);
        $this->makePolicy($manager, $client);

        $this->actingAs($manager);

        $widget = new class extends ClientsPoliciesTrendChart
        {
            public function exposeData(): array
            {
                return $this->getData();
            }

            public function exposeType(): string
            {
                return $this->getType();
            }
        };

        $data = $widget->exposeData();

        $this->assertTrue(ClientsPoliciesTrendChart::canView());

        $this->assertCount(2, $data['datasets']);
        $this->assertCount(6, $data['labels']);
        $this->assertCount(6, $data['datasets'][0]['data']);
        $this->assertCount(6, $data['datasets'][1]['data']);
        $this->assertSame('line', $widget->exposeType());
    }

    public function test_policy_status_chart_counts_policy_statuses(): void
    {
        $manager = $this->makeUser('manager');
        $client = $this->makeClient($manager);

        $this->makePolicy($manager, $client, null, ['status' => 'draft']);
        $this->makePolicy($manager, $client, null, ['status' => 'active']);
        $this->makePolicy($manager, $client, null, ['status' => 'completed']);
        $this->makePolicy($manager, $client, null, ['status' => 'canceled']);

        $this->actingAs($manager);

        $widget = new class extends PolicyStatusChart
        {
            public function exposeData(): array
            {
                return $this->getData();
            }

            public function exposeType(): string
            {
                return $this->getType();
            }
        };

        $data = $widget->exposeData();

        $this->assertSame([1, 1, 1, 1], $data['datasets'][0]['data']);
        $this->assertSame('doughnut', $widget->exposeType());
    }

    public function test_manager_policies_chart_is_visible_only_to_supervisor(): void
    {
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor');
        $manager = $this->makeUser('manager');

        $this->actingAs($admin);
        $this->assertFalse(ManagerPoliciesChart::canView());

        $this->actingAs($supervisor);
        $this->assertTrue(ManagerPoliciesChart::canView());

        $this->actingAs($manager);
        $this->assertFalse(ManagerPoliciesChart::canView());
    }
}