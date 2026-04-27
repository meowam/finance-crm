<?php

namespace Tests\Feature;

use App\Filament\Resources\ClaimNotes\ClaimNoteResource;
use App\Models\ClaimNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class ClaimNoteAccessTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_manager_sees_only_claim_notes_related_to_his_policies(): void
    {
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');

        $clientA = $this->makeClient($managerA);
        $clientB = $this->makeClient($managerB);

        $policyA = $this->makePolicy($managerA, $clientA, null, ['status' => 'active']);
        $policyB = $this->makePolicy($managerB, $clientB, null, ['status' => 'active']);

        $claimA = $this->makeClaim($policyA, $managerA);
        $claimB = $this->makeClaim($policyB, $managerB);

        $noteA = ClaimNote::create([
            'claim_id' => $claimA->id,
            'user_id' => $managerA->id,
            'visibility' => 'внутрішня',
            'note' => 'Нотатка по полісу менеджера A',
        ]);

        $noteB = ClaimNote::create([
            'claim_id' => $claimB->id,
            'user_id' => $managerB->id,
            'visibility' => 'внутрішня',
            'note' => 'Нотатка по полісу менеджера B',
        ]);

        $this->actingAs($managerA);

        $visibleIds = ClaimNoteResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($noteA->id, $visibleIds);
        $this->assertNotContains($noteB->id, $visibleIds);
    }

    public function test_supervisor_sees_claim_notes_for_all_managers(): void
    {
        $supervisor = $this->makeUser('supervisor');
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');

        $clientA = $this->makeClient($managerA);
        $clientB = $this->makeClient($managerB);

        $policyA = $this->makePolicy($managerA, $clientA, null, ['status' => 'active']);
        $policyB = $this->makePolicy($managerB, $clientB, null, ['status' => 'active']);

        $claimA = $this->makeClaim($policyA, $managerA);
        $claimB = $this->makeClaim($policyB, $managerB);

        $noteA = ClaimNote::create([
            'claim_id' => $claimA->id,
            'user_id' => $managerA->id,
            'visibility' => 'внутрішня',
            'note' => 'Нотатка по першому менеджеру',
        ]);

        $noteB = ClaimNote::create([
            'claim_id' => $claimB->id,
            'user_id' => $managerB->id,
            'visibility' => 'внутрішня',
            'note' => 'Нотатка по другому менеджеру',
        ]);

        $this->actingAs($supervisor);

        $visibleIds = ClaimNoteResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($noteA->id, $visibleIds);
        $this->assertContains($noteB->id, $visibleIds);
    }

    public function test_admin_sees_claim_notes_for_all_managers(): void
    {
        $admin = $this->makeUser('admin');
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');

        $clientA = $this->makeClient($managerA);
        $clientB = $this->makeClient($managerB);

        $policyA = $this->makePolicy($managerA, $clientA, null, ['status' => 'active']);
        $policyB = $this->makePolicy($managerB, $clientB, null, ['status' => 'active']);

        $claimA = $this->makeClaim($policyA, $managerA);
        $claimB = $this->makeClaim($policyB, $managerB);

        $noteA = ClaimNote::create([
            'claim_id' => $claimA->id,
            'user_id' => $managerA->id,
            'visibility' => 'внутрішня',
            'note' => 'Нотатка по першому менеджеру',
        ]);

        $noteB = ClaimNote::create([
            'claim_id' => $claimB->id,
            'user_id' => $managerB->id,
            'visibility' => 'внутрішня',
            'note' => 'Нотатка по другому менеджеру',
        ]);

        $this->actingAs($admin);

        $visibleIds = ClaimNoteResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($noteA->id, $visibleIds);
        $this->assertContains($noteB->id, $visibleIds);
    }

    public function test_claim_note_policy_allows_manager_only_for_notes_related_to_his_policies(): void
    {
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');

        $clientA = $this->makeClient($managerA);
        $clientB = $this->makeClient($managerB);

        $policyA = $this->makePolicy($managerA, $clientA, null, ['status' => 'active']);
        $policyB = $this->makePolicy($managerB, $clientB, null, ['status' => 'active']);

        $claimA = $this->makeClaim($policyA, $managerA);
        $claimB = $this->makeClaim($policyB, $managerB);

        $ownNote = ClaimNote::create([
            'claim_id' => $claimA->id,
            'user_id' => $managerA->id,
            'visibility' => 'внутрішня',
            'note' => 'Своя нотатка',
        ]);

        $foreignNote = ClaimNote::create([
            'claim_id' => $claimB->id,
            'user_id' => $managerB->id,
            'visibility' => 'внутрішня',
            'note' => 'Чужа нотатка',
        ]);

        $this->assertTrue($managerA->can('view', $ownNote));
        $this->assertTrue($managerA->can('update', $ownNote));

        $this->assertFalse($managerA->can('view', $foreignNote));
        $this->assertFalse($managerA->can('update', $foreignNote));

        $this->assertFalse($managerA->can('delete', $ownNote));
        $this->assertFalse($managerA->can('delete', $foreignNote));
    }

    public function test_supervisor_and_admin_can_manage_claim_notes(): void
    {
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor');
        $manager = $this->makeUser('manager');

        $client = $this->makeClient($manager);
        $policy = $this->makePolicy($manager, $client, null, ['status' => 'active']);
        $claim = $this->makeClaim($policy, $manager);

        $note = ClaimNote::create([
            'claim_id' => $claim->id,
            'user_id' => $manager->id,
            'visibility' => 'внутрішня',
            'note' => 'Нотатка для перевірки доступів',
        ]);

        $this->assertTrue($supervisor->can('view', $note));
        $this->assertTrue($supervisor->can('update', $note));
        $this->assertTrue($supervisor->can('delete', $note));

        $this->assertTrue($admin->can('view', $note));
        $this->assertTrue($admin->can('update', $note));
        $this->assertTrue($admin->can('delete', $note));
    }
}