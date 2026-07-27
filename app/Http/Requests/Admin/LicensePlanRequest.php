<?php

namespace App\Http\Requests\Admin;

use App\Enums\LicenseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class LicensePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage license plans') ?? false;
    }

    public function rules(): array
    {
        $planId = $this->route('license_plan')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('license_plans', 'name')->ignore($planId)],
            'type' => ['required', new Enum(LicenseType::class)],
            'duration_in_days' => ['nullable', 'integer', 'min:1', 'required_unless:type,lifetime'],
            'price' => ['required', 'numeric', 'min:0'],
            'maximum_tracking_slots' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
