<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\Schemas\ClientForm;
use App\Filament\Resources\LeadRequests\Schemas\LeadRequestForm;
use App\Services\Reports\ManagerReportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class SourceDictionaryTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_client_and_lead_forms_use_same_source_dictionary(): void
    {
        $expected = [
            'office' => 'Офіс',
            'landing' => 'Лендінг',
            'recommendation' => 'Рекомендація',
        ];

        $clientSourceOptions = new ReflectionMethod(ClientForm::class, 'sourceOptions');
        $clientSourceOptions->setAccessible(true);

        $leadSourceOptions = new ReflectionMethod(LeadRequestForm::class, 'sourceOptions');
        $leadSourceOptions->setAccessible(true);

        $this->assertSame($expected, $clientSourceOptions->invoke(null));
        $this->assertSame($expected, $leadSourceOptions->invoke(null));
    }

    public function test_reports_filter_clients_by_unified_source_dictionary(): void
    {
        $manager = $this->makeUser('manager');

        $this->makeClient($manager, [
            'status' => 'active',
            'source' => 'office',
        ]);

        $this->makeClient($manager, [
            'status' => 'active',
            'source' => 'landing',
        ]);

        $this->makeClient($manager, [
            'status' => 'active',
            'source' => 'recommendation',
        ]);

        $service = app(ManagerReportsService::class);

        $officeRows = $service->getSummaryRows([
            'date_from' => now()->subDay()->toDateString(),
            'date_until' => now()->addDay()->toDateString(),
            'manager_id' => $manager->id,
            'client_source' => 'office',
            'policy_status' => null,
        ], $manager);

        $landingRows = $service->getSummaryRows([
            'date_from' => now()->subDay()->toDateString(),
            'date_until' => now()->addDay()->toDateString(),
            'manager_id' => $manager->id,
            'client_source' => 'landing',
            'policy_status' => null,
        ], $manager);

        $recommendationRows = $service->getSummaryRows([
            'date_from' => now()->subDay()->toDateString(),
            'date_until' => now()->addDay()->toDateString(),
            'manager_id' => $manager->id,
            'client_source' => 'recommendation',
            'policy_status' => null,
        ], $manager);

        $this->assertSame(1, $officeRows[0]['new_clients']);
        $this->assertSame(1, $landingRows[0]['new_clients']);
        $this->assertSame(1, $recommendationRows[0]['new_clients']);
    }
}