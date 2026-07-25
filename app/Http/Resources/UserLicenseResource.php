<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLicenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'license_number' => $this->license_number,
            'plan' => new LicensePlanResource($this->whenLoaded('plan')),
            'purchase_date' => $this->purchase_date,
            'activation_date' => $this->activation_date,
            'expiry_date' => $this->expiry_date,
            'status' => $this->status?->value,
            'remaining_slots' => $this->remaining_slots,
            'consumed_slots' => $this->consumed_slots,
            'payment_status' => $this->payment_status?->value,
            'invoice_number' => $this->invoice_number,
            'order_number' => $this->order_number,
        ];
    }
}
