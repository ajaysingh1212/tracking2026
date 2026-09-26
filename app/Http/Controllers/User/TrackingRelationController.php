<?php

namespace App\Http\Controllers\User;

use App\Enums\PaymentStatus;
use App\Enums\LicenseStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\TrackingRelationStoreRequest;
use App\Models\TrackingRelation;
use App\Models\User;
use App\Services\TrackingRelationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TrackingRelationController extends Controller
{
    public function __construct(protected TrackingRelationService $relations) {}

    public function index(): View
    {
        $user = User::query()->findOrFail(Auth::id());

        return view('user.tracking-relations.index', [
            'relations' => $user->trackedUsers()->with(['trackedUser', 'userLicense.plan'])->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        $user = User::query()->findOrFail(Auth::id());

        return view('user.tracking-relations.create', [
            'users' => User::query()->whereKeyNot($user->id)->orderBy('name')->get(),
            'availableLicenseCount' => $user->userLicenses()
                ->where('payment_status', PaymentStatus::Paid)
                ->whereIn('status', [LicenseStatus::Pending, LicenseStatus::Active])
                ->whereNull('assigned_tracked_user_id')
                ->count(),
        ]);
    }

    public function store(TrackingRelationStoreRequest $request): RedirectResponse
    {
        $user = User::query()->findOrFail(Auth::id());
        $this->relations->create([
            'tracker_user_id' => $user->id,
            'tracked_user_id' => $request->integer('tracked_user_id'),
            'relationship_name' => $request->validated('relationship_name'),
            'status' => UserStatus::Active->value,
            'created_by' => $user->id,
        ]);

        return redirect()->route('my-tracking.index')->with('status', 'Tracking relation created.');
    }

    public function destroy(TrackingRelation $trackingRelation): RedirectResponse
    {
        abort_unless((int) $trackingRelation->tracker_user_id === (int) Auth::id(), 403);
        $this->relations->delete($trackingRelation);

        return redirect()->route('my-tracking.index')->with('status', 'Tracking relation removed. The assigned license remains bound to this person.');
    }
}