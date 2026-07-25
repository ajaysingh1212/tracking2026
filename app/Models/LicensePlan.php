<?php

namespace App\Models;

use App\Enums\LicenseType;
use App\Enums\UserStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LicensePlan extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'type',
        'duration_in_days',
        'price',
        'maximum_tracking_slots',
        'status',
        'description',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => LicenseType::class,
            'status' => UserStatus::class,
            'price' => 'decimal:2',
        ];
    }

    public function userLicenses(): HasMany
    {
        return $this->hasMany(UserLicense::class);
    }
}
