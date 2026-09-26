@extends('layouts.app')

@section('page-eyebrow', 'Licensing')
@section('page-title', 'Choose a License')

@section('page-actions')
    <a href="{{ route('my-licenses.index') }}" class="btn tracker-outline-btn"><i class="fa-solid fa-arrow-left me-2"></i>My Licenses</a>
@endsection

@section('content')
    <div class="row g-3">
        @forelse ($plans as $plan)
            <div class="col-xl-4 col-md-6">
                <div class="card tracker-surface-card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <h2 class="tracker-card-title mb-1">{{ $plan->name }}</h2>
                                <div class="text-muted">{{ $plan->duration_in_days ? $plan->duration_in_days.' days from first use' : 'Lifetime from first use' }}</div>
                            </div>
                            @if ($plan->is_free)
                                <span class="badge text-bg-success">Free</span>
                            @endif
                        </div>
                        <p class="mt-3 mb-4">{{ $plan->description }}</p>
                        <div class="mt-auto">
                            <div class="fs-4 fw-bold"><i class="fa-solid fa-indian-rupee-sign" aria-hidden="true"></i> {{ number_format($plan->price, 2) }}</div>
                            @if (! $plan->is_free)
                                <div class="small text-muted mb-3">Renewal: <i class="fa-solid fa-indian-rupee-sign" aria-hidden="true"></i> {{ number_format($plan->renewal_price, 2) }}</div>
                            @endif
                            @if ($plan->is_free && $freeLicenseClaimed)
                                <button type="button" class="btn tracker-outline-btn w-100" disabled>Free license already claimed</button>
                            @else
                                <form method="POST" action="{{ route('my-licenses.purchase') }}">
                                    @csrf
                                    <input type="hidden" name="license_plan_id" value="{{ $plan->id }}">
                                    <button type="submit" class="btn tracker-primary-btn w-100">{{ $plan->is_free ? 'Claim Free License' : 'Buy License' }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="tracker-empty-state"><i class="fa-solid fa-id-card"></i><p class="mb-0">No licenses are available right now.</p></div></div>
        @endforelse
    </div>
@endsection