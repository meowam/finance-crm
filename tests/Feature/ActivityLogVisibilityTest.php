<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesDomainObjects;
use Tests\TestCase;

class ActivityLogVisibilityTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainObjects;

    public function test_activity_log_is_created_for_create_update_and_delete(): void
    {
        $admin = $this->makeUser('admin');
        DB::table('activity_logs')->delete();

        $this->actingAs($admin);

        $client = $this->makeClient($admin, [
            'primary_email' => 'activity@example.com',
            'primary_phone' => '+380671112233',
            'document_number' => 'AA654321',
        ]);

        $createdLog = ActivityLog::query()
            ->where('subject_type', $client::class)
            ->where('subject_id', $client->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($createdLog);
        $this->assertEquals($admin->id, $createdLog->actor_id);
        $this->assertNotEmpty($createdLog->after);

        $client->update([
            'city' => 'Львів',
            'status' => 'active',
        ]);

        $updatedLog = ActivityLog::query()
            ->where('subject_type', $client::class)
            ->where('subject_id', $client->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($updatedLog);
        $this->assertEquals('Київ', $updatedLog->before['city']);
        $this->assertEquals('Львів', $updatedLog->after['city']);
        $this->assertEquals('lead', $updatedLog->before['status']);
        $this->assertEquals('active', $updatedLog->after['status']);

        $client->delete();

        $deletedLog = ActivityLog::query()
            ->where('subject_type', $client::class)
            ->where('action', 'deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($deletedLog);
        $this->assertArrayHasKey('primary_email', $deletedLog->before);
        $this->assertNull($deletedLog->after);
    }

    public function test_activity_log_visibility_differs_by_role(): void
    {
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor');
        $managerA = $this->makeUser('manager');
        $managerB = $this->makeUser('manager');

        DB::table('activity_logs')->delete();

        $this->actingAs($managerA);
        $clientA = $this->makeClient($managerA);

        $this->actingAs($managerB);
        $clientB = $this->makeClient($managerB);

        $this->actingAs($supervisor);
        $clientA->update(['city' => 'Одеса']);

        $this->actingAs($admin);
        $clientB->update(['city' => 'Дніпро']);

        $adminVisible = ActivityLog::query()->visibleTo($admin)->get();
        $supervisorVisible = ActivityLog::query()->visibleTo($supervisor)->get();
        $managerAVisible = ActivityLog::query()->visibleTo($managerA)->get();
        $managerBVisible = ActivityLog::query()->visibleTo($managerB)->get();

        $this->assertGreaterThanOrEqual(4, $adminVisible->count());

        $this->assertTrue($supervisorVisible->every(function (ActivityLog $log) use ($supervisor) {
            return $log->actor_role === 'manager' || (int) $log->actor_id === (int) $supervisor->id;
        }));

        $this->assertTrue($managerAVisible->every(fn (ActivityLog $log) => (int) $log->actor_id === (int) $managerA->id));
        $this->assertTrue($managerBVisible->every(fn (ActivityLog $log) => (int) $log->actor_id === (int) $managerB->id));
    }
}