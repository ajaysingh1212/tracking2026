<?php

namespace Tests\Feature;

use App\Models\TrackingRelation;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BroadcastingChannelTest extends TestCase
{
    use RefreshDatabase;

    private function authorize(User $user, User $subject): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($user);

        return $this->postJson('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-App.Models.User.'.$subject->id,
        ]);
    }

    public function test_a_user_can_authorize_their_own_channel(): void
    {
        $user = User::factory()->create();

        $this->authorize($user, $user)->assertOk();
    }

    public function test_an_unrelated_user_cannot_authorize_someone_elses_channel(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        $this->authorize($user, $stranger)->assertForbidden();
    }

    public function test_a_tracker_can_authorize_their_tracked_users_channel(): void
    {
        $tracker = User::factory()->create();
        $tracked = User::factory()->create();

        TrackingRelation::create([
            'tracker_user_id' => $tracker->id,
            'tracked_user_id' => $tracked->id,
            'relationship_name' => 'Test Relation',
            'status' => 'active',
        ]);

        $this->authorize($tracker, $tracked)->assertOk();
    }

    public function test_an_inactive_tracking_relation_does_not_authorize(): void
    {
        $tracker = User::factory()->create();
        $tracked = User::factory()->create();

        TrackingRelation::create([
            'tracker_user_id' => $tracker->id,
            'tracked_user_id' => $tracked->id,
            'relationship_name' => 'Test Relation',
            'status' => 'inactive',
        ]);

        $this->authorize($tracker, $tracked)->assertForbidden();
    }

    public function test_a_privileged_role_can_authorize_any_users_channel(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $anyone = User::factory()->create();

        $this->authorize($admin, $anyone)->assertOk();
    }
}
