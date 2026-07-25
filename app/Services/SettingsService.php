<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        return Setting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public function set(string $group, string $key, mixed $value, string $type = 'string', bool $isPublic = false, ?string $description = null): Setting
    {
        return Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value, 'type' => $type, 'is_public' => $isPublic, 'description' => $description],
        );
    }
}
