<?php

namespace Tests\Feature\Admin;

use App\Models\LicensePlan;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class TrackingRelationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_plain_user_cannot_manage_tracking_relations(): void
    {
        $this->actingAsPlainUser();

        $this->get(route('admin.tracking-relations.index'))->assertForbidden();
    }

    public function test_creating_a_relation_consumes_a_license_slot_and_deleting_returns_it(): void
    {
        $this->actingAsSuperAdmin();
        $this->seedSettings();

        $plan = LicensePlan::create([
            'uuid' => Str::uuid(),
            'name' => 'Tracking Plan',
            'type' => 'yearly',
            'duration_in_days' => 365,
            'price' => 99,
            'maximum_tracking_slots' => 5,
            'status' => 'active',
            'display_order' => 1,
        ]);

        $tracker = User::factory()->create();
        $tracked = User::factory()->create();
        app(LicenseService::class)->purchase($tracker, $plan);

        $createResponse = $this->post(route('admin.tracking-relations.store'), [
            'tracker_user_id' => $tracker->id,
            'tracked_user_id' => $tracked->id,
            'relationship_name' => 'Field Manager',
            'status' => 'active',
        ]);

        $createResponse->assertSessionHasNoErrors()->assertRedirect(route('admin.tracking-relations.index'));

        $license = $tracker->userLicenses()->first();
        $this->assertSame(4, $license->remaining_slots);
        $this->assertSame(1, $license->consumed_slots);

        $relation = $tracker->trackedUsers()->first();
        $this->assertNotNull($relation);

        $deleteResponse = $this->delete(route('admin.tracking-relations.destroy', $relation));
        $deleteResponse->assertRedirect(route('admin.tracking-relations.index'));

        $this->assertSame(5, $license->fresh()->remaining_slots);
        $this->assertSame(0, $license->fresh()->consumed_slots);
        $this->assertSoftDeleted('tracking_relations', ['id' => $relation->id]);
    }

    public function test_creating_a_relation_without_available_slots_fails_validation(): void
    {
        $this->actingAsSuperAdmin();
        $tracker = User::factory()->create();
        $tracked = User::factory()->create();

        $response = $this->post(route('admin.tracking-relations.store'), [
            'tracker_user_id' => $tracker->id,
            'tracked_user_id' => $tracked->id,
            'relationship_name' => 'No License',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('tracker_user_id');
        $this->assertDatabaseCount('tracking_relations', 0);
    }
}
