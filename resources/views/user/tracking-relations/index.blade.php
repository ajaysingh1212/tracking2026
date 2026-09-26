@extends('layouts.app')

@section('page-eyebrow', 'Workspace')
@section('page-title', 'My Tracking')

@section('page-actions')
    <a href="{{ route('my-tracking.create') }}" class="btn tracker-primary-btn"><i class="fa-solid fa-plus me-2"></i>Add Person</a>
@endsection

@section('content')
    <div class="card tracker-surface-card">
        <div class="card-body table-responsive">
            <table class="table tracker-table align-middle">
                <thead><tr><th>Person</th><th>Relationship</th><th>License</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($relations as $relation)
                        <tr>
                            <td>{{ $relation->trackedUser?->name }}</td>
                            <td>{{ $relation->relationship_name }}</td>
                            <td>{{ $relation->userLicense?->license_number }}</td>
                            <td>{{ $relation->userLicense?->expiry_date?->format('d M Y') ?? 'Lifetime' }}</td>
                            <td>@include('admin.partials.status-pill', ['status' => $relation->status])</td>
                            <td>
                                <form method="POST" action="{{ route('my-tracking.destroy', $relation) }}" data-confirm-delete data-confirm-title="Remove this person?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove relation"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="tracker-empty-state"><i class="fa-solid fa-users"></i><p class="mb-0">No tracked people yet.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($relations->hasPages())
            <div class="card-footer border-0 bg-transparent">{{ $relations->links() }}</div>
        @endif
    </div>
@endsection