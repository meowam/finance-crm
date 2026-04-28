<?php

namespace Tests\Feature;

use App\Filament\Widgets\AdminProblemRecordsTable;
use App\Models\ProblemRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class ProblemRecordsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_problem_records_include_active_leads_and_clients_without_active_manager(): void
    {
        $inactiveManager = $this->makeUser('manager', [
            'is_active' => false,
        ]);

        $activeManager = $this->makeUser('manager', [
            'is_active' => true,
        ]);

        $problemLead = $this->makeLeadRequest($inactiveManager, [
            'status' => 'new',
            'source' => 'office',
        ]);

        $problemClient = $this->makeClient($inactiveManager, [
            'status' => 'active',
            'source' => 'office',
        ]);

        $healthyLead = $this->makeLeadRequest($activeManager, [
            'status' => 'new',
            'source' => 'office',
        ]);

        $healthyClient = $this->makeClient($activeManager, [
            'status' => 'active',
            'source' => 'office',
        ]);

        $records = $this->getProblemRecords();

        $recordIds = $records
            ->pluck('id')
            ->all();

        $this->assertContains("lead-{$problemLead->id}", $recordIds);
        $this->assertContains("client-{$problemClient->id}", $recordIds);

        $this->assertNotContains("lead-{$healthyLead->id}", $recordIds);
        $this->assertNotContains("client-{$healthyClient->id}", $recordIds);
    }

    public function test_problem_records_include_leads_and_clients_without_manager(): void
    {
        $manager = $this->makeUser('manager');

        $leadWithoutManager = $this->makeLeadRequest($manager, [
            'status' => 'in_progress',
            'source' => 'office',
            'assigned_user_id' => null,
        ]);

        $clientWithoutManager = $this->makeClient($manager, [
            'status' => 'active',
            'source' => 'office',
            'assigned_user_id' => null,
        ]);

        $records = $this->getProblemRecords();

        $recordIds = $records
            ->pluck('id')
            ->all();

        $this->assertContains("lead-{$leadWithoutManager->id}", $recordIds);
        $this->assertContains("client-{$clientWithoutManager->id}", $recordIds);
    }

    public function test_problem_records_ignore_inactive_lead_statuses_and_inactive_client_statuses(): void
    {
        $inactiveManager = $this->makeUser('manager', [
            'is_active' => false,
        ]);

        $rejectedLead = $this->makeLeadRequest($inactiveManager, [
            'status' => 'rejected',
            'source' => 'office',
        ]);

        $convertedLead = $this->makeLeadRequest($inactiveManager, [
            'status' => 'converted',
            'source' => 'office',
            'converted_client_id' => null,
        ]);

        $leadClient = $this->makeClient($inactiveManager, [
            'status' => 'lead',
            'source' => 'office',
        ]);

        $archivedClient = $this->makeClient($inactiveManager, [
            'status' => 'archived',
            'source' => 'office',
        ]);

        $records = $this->getProblemRecords();

        $recordIds = $records
            ->pluck('id')
            ->all();

        $this->assertNotContains("lead-{$rejectedLead->id}", $recordIds);
        $this->assertNotContains("lead-{$convertedLead->id}", $recordIds);

        $this->assertNotContains("client-{$leadClient->id}", $recordIds);
        $this->assertNotContains("client-{$archivedClient->id}", $recordIds);
    }

    public function test_admin_and_supervisor_can_view_problem_records_widget(): void
    {
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor');
        $manager = $this->makeUser('manager');

        $this->actingAs($admin);
        $this->assertTrue(AdminProblemRecordsTable::canView());

        $this->actingAs($supervisor);
        $this->assertTrue(AdminProblemRecordsTable::canView());

        $this->actingAs($manager);
        $this->assertFalse(AdminProblemRecordsTable::canView());
    }

    /**
     * @return Collection<int, ProblemRecord>
     */
    protected function getProblemRecords(): Collection
    {
        $widget = app(AdminProblemRecordsTable::class);

        $method = new ReflectionMethod(AdminProblemRecordsTable::class, 'getProblemRecordsQuery');
        $method->setAccessible(true);

        return $method
            ->invoke($widget)
            ->get();
    }
}