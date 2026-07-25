<?php

namespace Database\Seeders;

use App\Enums\LicenseType;
use App\Enums\UserStatus;
use App\Models\LicensePlan;
use Illuminate\Database\Seeder;

class LicensePlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Monthly Starter', 'type' => LicenseType::Monthly, 'duration_in_days' => 30, 'price' => 29.00, 'maximum_tracking_slots' => 10, 'display_order' => 1],
            ['name' => 'Quarterly Growth', 'type' => LicenseType::Quarterly, 'duration_in_days' => 90, 'price' => 79.00, 'maximum_tracking_slots' => 30, 'display_order' => 2],
            ['name' => 'Half Yearly Business', 'type' => LicenseType::HalfYearly, 'duration_in_days' => 180, 'price' => 149.00, 'maximum_tracking_slots' => 75, 'display_order' => 3],
            ['name' => 'Yearly Enterprise', 'type' => LicenseType::Yearly, 'duration_in_days' => 365, 'price' => 249.00, 'maximum_tracking_slots' => 150, 'display_order' => 4],
            ['name' => 'Lifetime Corporate', 'type' => LicenseType::Lifetime, 'duration_in_days' => null, 'price' => 999.00, 'maximum_tracking_slots' => 500, 'display_order' => 5],
        ];

        foreach ($plans as $plan) {
            LicensePlan::query()->updateOrCreate(
                ['name' => $plan['name']],
                $plan + [
                    'status' => UserStatus::Active,
                    'description' => $plan['name'].' plan for Tracker Enterprise customers.',
                ],
            );
        }
    }
}
