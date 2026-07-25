<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSession extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'device_name',
        'platform',
        'browser',
        'ip_address',
        'country',
        'last_login_at',
        'last_activity_at',
        'logged_out_at',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'logged_out_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
