<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserLicense extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'license_plan_id',
        'license_number',
        'purchase_date',
        'activation_date',
        'expiry_date',
        'status',
        'remaining_slots',
        'consumed_slots',
        'payment_status',
        'invoice_number',
        'order_number',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'datetime',
            'activation_date' => 'datetime',
            'expiry_date' => 'datetime',
            'status' => LicenseStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LicensePlan::class, 'license_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
