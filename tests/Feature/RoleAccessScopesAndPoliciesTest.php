<?php

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\Policy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class RoleAccessScopesAndPoliciesTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_manager_sees_only_own_entities_in_scopes(): void
    {
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');
        $supervisor = $this->makeUser('supervisor');
        $admin = $this->makeUser('admin');

        $clientA = $this->makeClient($managerA);
        $clientB = $this->makeClient($managerB);

        $policyA = $this->makePolicy($managerA, $clientA);
        $policyB = $this->makePolicy($managerB, $clientB);

        $claimA = $this->makeClaim($policyA, $managerA);
        $claimB = $this->makeClaim($policyB, $managerB);

        $leadA = $this->makeLeadRequest($managerA);
        $leadB = $this->makeLeadRequest($managerB);

        $this->assertEquals([$clientA->id], Client::query()->visibleTo($managerA)->pluck('id')->all());
        $this->assertEquals([$policyA->id], Policy::query()->visibleTo($managerA)->pluck('id')->all());
        $this->assertEquals([$claimA->id], Claim::query()->visibleTo($managerA)->pluck('id')->all());
        $this->assertEquals([$leadA->id], LeadRequest::query()->visibleTo($managerA)->pluck('id')->all());

        $this->assertCount(2, Client::query()->visibleTo($supervisor)->get());
        $this->assertCount(2, Policy::query()->visibleTo($supervisor)->get());
        $this->assertCount(2, Claim::query()->visibleTo($supervisor)->get());
        $this->assertCount(2, LeadRequest::query()->visibleTo($supervisor)->get());

        $this->assertCount(2, Client::query()->visibleTo($admin)->get());
        $this->assertCount(2, Policy::query()->visibleTo($admin)->get());
        $this->assertCount(2, Claim::query()->visibleTo($admin)->get());
        $this->assertCount(2, LeadRequest::query()->visibleTo($admin)->get());
    }

    public function test_client_policy_allows_manager_only_own_record_but_not_delete(): void
    {
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');
        $supervisor = $this->makeUser('supervisor');
        $admin = $this->makeUser('admin');

        $clientA = $this->makeClient($managerA);
        $clientB = $this->makeClient($managerB);

        $this->assertTrue($managerA->can('view', $clientA));
        $this->assertTrue($managerA->can('update', $clientA));
        $this->assertFalse($managerA->can('view', $clientB));
        $this->assertFalse($managerA->can('update', $clientB));
        $this->assertFalse($managerA->can('delete', $clientA));
        $this->assertFalse($managerA->can('deleteAny', Client::class));

        $this->assertTrue($supervisor->can('view', $clientA));
        $this->assertTrue($supervisor->can('delete', $clientA));
        $this->assertTrue($supervisor->can('deleteAny', Client::class));

        $this->assertTrue($admin->can('view', $clientB));
        $this->assertTrue($admin->can('delete', $clientB));
        $this->assertTrue($admin->can('deleteAny', Client::class));
    }

    public function test_policy_policy_allows_manager_only_own_record_but_not_delete(): void
    {
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');
        $supervisor = $this->makeUser('supervisor');
        $admin = $this->makeUser('admin');

        $policyA = $this->makePolicy($managerA);
        $policyB = $this->makePolicy($managerB);

        $this->assertTrue($managerA->can('view', $policyA));
        $this->assertTrue($managerA->can('update', $policyA));
        $this->assertFalse($managerA->can('view', $policyB));
        $this->assertFalse($managerA->can('update', $policyB));
        $this->assertFalse($managerA->can('delete', $policyA));
        $this->assertFalse($managerA->can('deleteAny', Policy::class));

        $this->assertTrue($supervisor->can('view', $policyA));
        $this->assertTrue($supervisor->can('delete', $policyA));
        $this->assertTrue($supervisor->can('deleteAny', Policy::class));

        $this->assertTrue($admin->can('view', $policyB));
        $this->assertTrue($admin->can('delete', $policyB));
        $this->assertTrue($admin->can('deleteAny', Policy::class));
    }

    public function test_claim_policy_is_bound_to_policy_agent_visibility(): void
    {
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');
        $supervisor = $this->makeUser('supervisor');
        $admin = $this->makeUser('admin');

        $policyA = $this->makePolicy($managerA);
        $policyB = $this->makePolicy($managerB);

        $claimA = $this->makeClaim($policyA, $managerA);
        $claimB = $this->makeClaim($policyB, $managerB);

        $this->assertTrue($managerA->can('view', $claimA));
        $this->assertTrue($managerA->can('update', $claimA));
        $this->assertFalse($managerA->can('view', $claimB));
        $this->assertFalse($managerA->can('update', $claimB));
        $this->assertFalse($managerA->can('delete', $claimA));
        $this->assertFalse($managerA->can('deleteAny', Claim::class));

        $this->assertTrue($supervisor->can('view', $claimA));
        $this->assertTrue($supervisor->can('delete', $claimA));
        $this->assertTrue($supervisor->can('deleteAny', Claim::class));

        $this->assertTrue($admin->can('view', $claimB));
        $this->assertTrue($admin->can('delete', $claimB));
        $this->assertTrue($admin->can('deleteAny', Claim::class));
    }

    public function test_lead_request_policy_allows_manager_only_own_record_but_not_delete(): void
    {
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');
        $supervisor = $this->makeUser('supervisor');
        $admin = $this->makeUser('admin');

        $leadA = $this->makeLeadRequest($managerA);
        $leadB = $this->makeLeadRequest($managerB);

        $this->assertTrue($managerA->can('view', $leadA));
        $this->assertTrue($managerA->can('update', $leadA));
        $this->assertFalse($managerA->can('view', $leadB));
        $this->assertFalse($managerA->can('update', $leadB));
        $this->assertFalse($managerA->can('delete', $leadA));
        $this->assertFalse($managerA->can('deleteAny', LeadRequest::class));

        $this->assertTrue($supervisor->can('view', $leadA));
        $this->assertTrue($supervisor->can('delete', $leadA));
        $this->assertTrue($supervisor->can('deleteAny', LeadRequest::class));

        $this->assertTrue($admin->can('view', $leadB));
        $this->assertTrue($admin->can('delete', $leadB));
        $this->assertTrue($admin->can('deleteAny', LeadRequest::class));
    }
}