@extends('layouts.app')

@section('page-eyebrow', 'System')
@section('page-title', 'Settings')

@section('content')
    @php
        $tabs = [
            'site' => ['label' => 'Site & Branding', 'icon' => 'fa-solid fa-building'],
            'system' => ['label' => 'System', 'icon' => 'fa-solid fa-gears'],
            'license' => ['label' => 'License', 'icon' => 'fa-solid fa-id-card'],
            'security' => ['label' => 'Security', 'icon' => 'fa-solid fa-shield-halved'],
            'appearance' => ['label' => 'Appearance', 'icon' => 'fa-solid fa-palette'],
        ];
    @endphp

    <div class="card tracker-surface-card">
        <div class="card-header border-0 bg-transparent">
            <ul class="nav tracker-nav-tabs" role="tablist">
                @foreach ($tabs as $key => $tab)
                    <li class="nav-item">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $key }}" type="button">
                            <i class="{{ $tab['icon'] }} me-2"></i>{{ $tab['label'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                @foreach ($tabs as $key => $tab)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $key }}">
                        @include('admin.settings._group-form', ['groupKey' => $key])
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
