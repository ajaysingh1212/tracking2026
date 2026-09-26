@extends('layouts.app')

@section('page-eyebrow', 'Workspace')
@section('page-title', 'Add Tracked Person')

@section('content')
    <div class="card tracker-surface-card">
        <div class="card-body">
            @if ($availableLicenseCount < 1)
                <div class="alert alert-warning tracker-alert d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <span>You need an available license to add a tracked person.</span>
                    <a href="{{ route('my-licenses.plans') }}" class="btn tracker-primary-btn">Buy a License</a>
                </div>
            @endif
            @if ($errors->has('tracker_user_id'))
                <div class="alert alert-danger tracker-alert">{{ $errors->first('tracker_user_id') }} <a href="{{ route('my-licenses.plans') }}">Browse licenses</a></div>
            @endif
            <form method="POST" action="{{ route('my-tracking.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="tracker-form-label" for="tracked_user_id">Person to Track</label>
                        <select id="tracked_user_id" name="tracked_user_id" class="form-select @error('tracked_user_id') is-invalid @enderror" required>
                            <option value="">Select a user</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('tracked_user_id') == $user->id)>{{ $user->name }} · {{ $user->email }}</option>
                            @endforeach
                        </select>
                        @error('tracked_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="tracker-form-label" for="relationship_name">Relationship</label>
                        <input id="relationship_name" name="relationship_name" type="text" maxlength="120" class="form-control @error('relationship_name') is-invalid @enderror" value="{{ old('relationship_name') }}" placeholder="e.g. Field Manager" required>
                        @error('relationship_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn tracker-primary-btn">Create Relation</button>
                    <a href="{{ route('my-tracking.index') }}" class="btn tracker-outline-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection