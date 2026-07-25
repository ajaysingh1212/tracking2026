<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('site', 'name', 'Tracker Enterprise', 'string', true, 'Application display name');
        $settings->set('site', 'support_email', 'support@example.com', 'string', true, 'Support email address');
        $settings->set('site', 'support_phone', '+1-000-000-0000', 'string', true, 'Support phone number');
        $settings->set('system', 'timezone', 'UTC', 'string', false, 'Default application timezone');
        $settings->set('system', 'registration', true, 'boolean', false, 'Allow public registration');
        $settings->set('system', 'maintenance_mode', false, 'boolean', false, 'Application maintenance flag');
        $settings->set('license', 'return_slots_on_delete', true, 'boolean', false, 'Return slots when tracking relations are deleted');
        $settings->set('security', 'maximum_devices', 5, 'integer', false, 'Maximum concurrent user devices');
        $settings->set('security', 'password_policy', ['min' => 8, 'mixedCase' => true, 'numbers' => true, 'symbols' => false], 'json', false, 'Default password policy');
        $settings->set('appearance', 'theme', 'light', 'string', true, 'Default UI theme');
        $settings->set('appearance', 'language', 'en', 'string', true, 'Default application language');
    }
}
