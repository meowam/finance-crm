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

        $this->assertCount(4, $stats);
        $this->assertSame('1', $stats[0]->getValue());
        $this->assertSame('1', $stats[1]->getValue());
        $this->assertSame('1', $stats[2]->getValue());
    }

    public function test_overview_stats_for_admin_include_all_data(): void
    {
        $admin    = $this->makeUser('admin');
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');

        $clientA = $this->makeClient($managerA);
        $clientB = $this->makeClient($managerB);

        $this->makePolicy($managerA, $clientA, null, ['status' => 'active']);
        $this->makePolicy($managerB, $clientB, null, ['status' => 'draft']);

        $this->actingAs($admin);

        $widget = new class extends OverviewStats
        {
            public function exposeStats(): array
            {
                return $this->getStats();
            }
        };

        $stats = $widget->exposeStats();

        $this->assertSame('2', $stats[0]->getValue());
        $this->assertSame('2', $stats[1]->getValue());
    }

    public function test_clients_policies_trend_chart_returns_two_datasets(): void
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

        $this->assertCount(2, $data['datasets']);
        $this->assertCount(7, $data['labels']);
        $this->assertSame('line', $widget->exposeType());
    }

    public function test_policy_status_chart_counts_policy_statuses(): void
    {
        $manager = $this->makeUser('manager');
        $client  = $this->makeClient($manager);

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

    public function test_manager_policies_chart_is_visible_only_to_admin_and_supervisor(): void
    {
        $admin      = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor');
        $manager    = $this->makeUser('manager');

        $this->actingAs($admin);
        $this->assertTrue(ManagerPoliciesChart::canView());

        $this->actingAs($supervisor);
        $this->assertTrue(ManagerPoliciesChart::canView());

        $this->actingAs($manager);
        $this->assertFalse(ManagerPoliciesChart::canView());
    }
}
