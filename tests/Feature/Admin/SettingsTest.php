<?php

namespace Tests\Feature\Admin;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_plain_user_cannot_view_settings(): void
    {
        $this->actingAsPlainUser();

        $this->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_super_admin_can_update_system_settings(): void
    {
        $this->actingAsSuperAdmin();
        $this->seedSettings();

        $response = $this->put(route('admin.settings.update'), [
            'group' => 'system',
            'values' => [
                'timezone' => 'Asia/Kolkata',
                'registration' => '1',
                'maintenance_mode' => '0',
            ],
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('Asia/Kolkata', app(SettingsService::class)->get('system', 'timezone'));
        $this->assertTrue((bool) app(SettingsService::class)->get('system', 'registration'));
        $this->assertFalse((bool) app(SettingsService::class)->get('system', 'maintenance_mode'));
    }
}
