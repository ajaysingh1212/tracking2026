<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use App\Interfaces\Repositories\UserLicenseRepositoryInterface;
use App\Models\LicensePlan;
use App\Models\User;
use App\Models\UserLicense;
use Illuminate\Support\Str;

class LicenseService
{
    public function __construct(
        protected UserLicenseRepositoryInterface $licenses,
        protected ActivityLogService $activityLogService,
    ) {}

    public function purchase(User $user, LicensePlan $plan): UserLicense
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

    public function activate(UserLicense $license): UserLicense
    {
        $license->update([
            'status' => LicenseStatus::Active,
            'activation_date' => $license->activation_date ?? now(),
        ]);

        $this->activityLogService->log(auth()->user(), 'license.activated', $license, [
            'license_number' => $license->license_number,
        ]);

        return $license;
    }

    public function extend(UserLicense $license, int $days): UserLicense
    {
        $base = $license->expiry_date && $license->expiry_date->isFuture() ? $license->expiry_date : now();

        $license->update([
            'expiry_date' => $base->copy()->addDays($days),
            'status' => LicenseStatus::Active,
        ]);

        $this->activityLogService->log(auth()->user(), 'license.extended', $license, [
            'license_number' => $license->license_number,
            'days' => $days,
        ]);

        return $license;
    }

    public function cancel(UserLicense $license): UserLicense
    {
        $license->update(['status' => LicenseStatus::Cancelled]);

        $this->activityLogService->log(auth()->user(), 'license.cancelled', $license, [
            'license_number' => $license->license_number,
        ]);

        return $license;
    }
}
