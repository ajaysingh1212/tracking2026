<?php

namespace App\Modules\Reports;

use App\Enums\LicenseStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MonitoringReportAccessService
{
    public function visibleUsers(User $viewer): Collection
    {
        return $this->visibleUsersQuery($viewer)->orderBy('name')->get(['id', 'name']);
    }

    public function visibleUsersQuery(User $viewer): Builder
    {
        $query = User::query()
            ->where('id', '!=', $viewer->id)
            ->whereHas('userLicenses', fn (Builder $license) => $license
                ->where('status', LicenseStatus::Active)
                ->where(fn (Builder $expiry) => $expiry
                    ->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now())));

        if ($viewer->hasAnyRole(['Super Admin', 'Admin', 'Manager'])) {
            return $query->whereHas('trackerRelations', fn (Builder $relation) => $relation->where('status', UserStatus::Active));
        }

        return $query->whereHas('trackerRelations', fn (Builder $relation) => $relation
            ->where('tracker_user_id', $viewer->id)
            ->where('status', UserStatus::Active));
    }

    public function filtersFor(Request $request): array
    {
        $filters = $request->all();
        $viewer = $request->user();
        // Everyone can always pull their own report, even though `visibleUsers()`
        // (the "choose a person" dropdown) intentionally excludes the viewer.
        $allowedIds = $this->visibleUsers($viewer)->pluck('id')->push($viewer->id)->all();

        if ($request->filled('user_id')) {
            abort_unless(in_array((int) $request->input('user_id'), $allowedIds, true), 403);
            $filters['user_id'] = (int) $request->input('user_id');

            return $filters;
        }

        $filters['allowed_user_ids'] = $allowedIds;

        return $filters;
    }
}
