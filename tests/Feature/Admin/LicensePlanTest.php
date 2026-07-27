<?php

namespace Tests\Feature\Admin;

use App\Models\LicensePlan;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class LicensePlanTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_plain_user_cannot_manage_license_plans(): void
    {
        $this->actingAsPlainUser();

        $this->get(route('admin.license-plans.index'))->assertForbidden();
    }

    public function test_super_admin_can_create_a_license_plan(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->post(route('admin.license-plans.store'), [
            'name' => 'Test Plan',
            'type' => 'monthly',
            'duration_in_days' => 30,
            'price' => 49.99,
            'maximum_tracking_slots' => 10,
            'status' => 'active',
            'display_order' => 1,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.license-plans.index'));
        $this->assertDatabaseHas('license_plans', ['name' => 'Test Plan']);
    }

    public function test_super_admin_can_update_a_license_plan(): void
    {
        $this->actingAsSuperAdmin();
        $plan = LicensePlan::create([
            'uuid' => Str::uuid(),
            'name' => 'Original Plan',
            'type' => 'monthly',
            'duration_in_days' => 30,
            'price' => 19.99,
            'maximum_tracking_slots' => 5,
            'status' => 'active',
            'display_order' => 1,
        ]);

        $response = $this->put(route('admin.license-plans.update', $plan), [
            'name' => 'Renamed Plan',
            'type' => 'monthly',
            'duration_in_days' => 30,
            'price' => 29.99,
            'maximum_tracking_slots' => 8,
            'status' => 'active',
            'display_order' => 1,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.license-plans.index'));
        $this->assertSame('Renamed Plan', $plan->fresh()->name);
    }

    public function test_license_plan_with_active_licenses_cannot_be_deleted(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $plan = LicensePlan::create([
            'uuid' => Str::uuid(),
            'name' => 'In Use Plan',
            'type' => 'lifetime',
            'price' => 199,
            'maximum_tracking_slots' => 50,
            'status' => 'active',
            'display_order' => 1,
        ]);

        app(LicenseService::class)->purchase($admin, $plan);

        $this->delete(route('admin.license-plans.destroy', $plan))->assertForbidden();
        $this->assertDatabaseHas('license_plans', ['id' => $plan->id]);
    }
}
