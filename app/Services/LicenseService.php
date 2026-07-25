<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use App\Interfaces\Repositories\UserLicenseRepositoryInterface;
use App\Models\LicensePlan;
use App\Models\User;
use Illuminate\Support\Str;

class LicenseService
{
    public function __construct(
        protected UserLicenseRepositoryInterface $licenses,
        protected ActivityLogService $activityLogService,
    ) {
    }

    public function purchase(User $user, LicensePlan $plan): \App\Models\UserLicense
    {
        $purchaseDate = now();
        $activationDate = now();
        $expiryDate = $plan->type->value === 'lifetime'
            ? null
            : now()->addDays($plan->duration_in_days);

        $license = $this->licenses->create([
            'user_id' => $user->id,
            'license_plan_id' => $plan->id,
            'license_number' => strtoupper(Str::random(12)),
            'purchase_date' => $purchaseDate,
            'activation_date' => $activationDate,
            'expiry_date' => $expiryDate,
            'status' => LicenseStatus::Active,
            'remaining_slots' => $plan->maximum_tracking_slots,
            'consumed_slots' => 0,
            'payment_status' => PaymentStatus::Paid,
            'invoice_number' => 'INV-'.now()->format('YmdHis'),
            'order_number' => 'ORD-'.now()->format('YmdHis'),
        ]);

        $this->activityLogService->log($user, 'license.purchase', $license, [
            'plan' => $plan->name,
        ]);

        return $license;
    }
}
