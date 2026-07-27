@php
    $settings = $groups->get($groupKey, collect());
@endphp

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="row g-3">
    @csrf
    @method('PUT')
    <input type="hidden" name="group" value="{{ $groupKey }}">

    @forelse ($settings as $setting)
        <div class="col-md-6">
            <label class="tracker-form-label" for="setting_{{ $setting->key }}">{{ \Illuminate\Support\Str::of($setting->key)->replace('_', ' ')->headline() }}</label>

            @if ($setting->type === 'boolean')
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="values[{{ $setting->key }}]" id="setting_{{ $setting->key }}" value="1" @checked($setting->value)>
                </div>
            @elseif ($setting->type === 'integer')
                <input type="number" name="values[{{ $setting->key }}]" id="setting_{{ $setting->key }}" class="form-control" value="{{ $setting->value }}">
            @elseif ($setting->type === 'json')
                <textarea name="values[{{ $setting->key }}]" id="setting_{{ $setting->key }}" rows="2" class="form-control">{{ json_encode($setting->value) }}</textarea>
            @else
                <input type="text" name="values[{{ $setting->key }}]" id="setting_{{ $setting->key }}" class="form-control" value="{{ $setting->value }}">
            @endif

            @if ($setting->description)
                <span class="tracker-form-hint">{{ $setting->description }}</span>
            @endif
        </div>
    @empty
        <div class="col-12 text-muted">No settings configured in this group yet.</div>
    @endforelse

    @if ($groupKey === 'site')
        <div class="col-md-6">
            <label class="tracker-form-label" for="logo">Site Logo</label>
            <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
            @php $logo = $groups->get('site', collect())->firstWhere('key', 'logo'); @endphp
            @if ($logo?->value)
                <span class="tracker-form-hint">Current: {{ $logo->value }}</span>
            @endif
        </div>
        <div class="col-md-6">
            <label class="tracker-form-label" for="favicon">Favicon</label>
            <input type="file" name="favicon" id="favicon" class="form-control" accept="image/*">
            @php $favicon = $groups->get('site', collect())->firstWhere('key', 'favicon'); @endphp
            @if ($favicon?->value)
                <span class="tracker-form-hint">Current: {{ $favicon->value }}</span>
            @endif
        </div>
    @endif

    <div class="col-12">
        <button type="submit" class="btn tracker-primary-btn">Save {{ ucfirst($groupKey) }} Settings</button>
    </div>
</form>
