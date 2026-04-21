<?php

namespace Tests\Feature;

use App\Filament\Resources\LeadRequests\Tables\LeadRequestsTable;
use App\Models\Client;
use App\Models\LeadRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class LeadFlowTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    protected function leadHasExistingClient(LeadRequest $lead): bool
    {
        $method = new ReflectionMethod(LeadRequestsTable::class, 'hasExistingClient');
        $method->setAccessible(true);

        return $method->invoke(null, $lead);
    }

    public function test_individual_lead_uses_full_name_as_display_label(): void
    {
        $manager = $this->makeUser('manager');

        $lead = $this->makeLeadRequest($manager, [
            'type' => 'individual',
            'first_name' => 'Anastasiia',
            'last_name' => 'Khomenko',
            'middle_name' => null,
            'company_name' => null,
        ]);

        $this->assertSame('Khomenko Anastasiia', $lead->display_label);
    }

    public function test_company_lead_uses_company_name_as_display_label(): void
    {
        $manager = $this->makeUser('manager');

        $lead = $this->makeLeadRequest($manager, [
            'type' => 'company',
            'first_name' => 'Iryna',
            'last_name' => 'Bondar',
            'company_name' => 'ТОВ Ромашка',
        ]);

        $this->assertSame('ТОВ Ромашка', $lead->display_label);
    }

    public function test_lead_request_detects_existing_converted_client(): void
    {
        $manager = $this->makeUser('manager');
        $client = $this->makeClient($manager);

        $lead = $this->makeLeadRequest($manager, [
            'converted_client_id' => $client->id,
            'status' => 'converted',
        ])->fresh();

        $this->assertTrue($this->leadHasExistingClient($lead));
    }

    public function test_lead_request_allows_conversion_again_if_client_was_deleted(): void
    {
        $manager = $this->makeUser('manager');
        $client = $this->makeClient($manager);

        $lead = $this->makeLeadRequest($manager, [
            'converted_client_id' => $client->id,
            'status' => 'converted',
        ]);

        $client->delete();

        $lead = $lead->fresh();

        $this->assertFalse($this->leadHasExistingClient($lead));
    }

    public function test_lead_request_keeps_relation_to_converted_client_when_client_exists(): void
    {
        $manager = $this->makeUser('manager');
        $client = $this->makeClient($manager, [
            'company_name' => 'ТОВ Альфа',
            'type' => 'company',
        ]);

        $lead = $this->makeLeadRequest($manager, [
            'type' => 'company',
            'company_name' => 'ТОВ Альфа',
            'converted_client_id' => $client->id,
            'status' => 'converted',
        ])->fresh();

        $this->assertNotNull($lead->convertedClient);
        $this->assertSame($client->id, $lead->convertedClient->id);
    }
}