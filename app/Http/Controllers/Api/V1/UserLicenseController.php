<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLicenseStoreRequest;
use App\Http\Resources\UserLicenseResource;
use App\Models\LicensePlan;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserLicenseController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return UserLicenseResource::collection(
            $request->user()->userLicenses()->with('plan')->latest()->paginate()
        );
    }

    public function store(UserLicenseStoreRequest $request): UserLicenseResource
    {
        $plan = LicensePlan::query()->findOrFail($request->integer('license_plan_id'));

        return new UserLicenseResource(
            $this->licenseService->purchase($request->user(), $plan)
        );
    }
}
