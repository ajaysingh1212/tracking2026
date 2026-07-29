<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GeofenceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreGeofenceRequest;
use App\Http\Requests\Api\V1\UpdateGeofenceRequest;
use App\Http\Resources\GeofenceEventResource;
use App\Http\Resources\GeofenceResource;
use App\Models\Geofence;
use App\Services\Geofence\GeofenceManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class GeofenceController extends Controller
{
    public function __construct(
        protected GeofenceManagementService $geofenceService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Geofence::class);

        $query = Geofence::query()->with(['points', 'creator']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $geofences = $query->orderBy('name')->paginate(50);

        return GeofenceResource::collection($geofences);
    }

    public function store(StoreGeofenceRequest $request): GeofenceResource
    {
        $this->authorize('create', Geofence::class);

        $geofence = $this->geofenceService->create($request->validated(), $request->user());

        return new GeofenceResource($geofence);
    }

    public function show(Geofence $geofence): GeofenceResource
    {
        $this->authorize('view', $geofence);

        return new GeofenceResource($geofence->load(['points', 'creator']));
    }

    public function update(UpdateGeofenceRequest $request, Geofence $geofence): GeofenceResource
    {
        $this->authorize('update', $geofence);

        $geofence = $this->geofenceService->update($geofence, $request->validated(), $request->user());

        return new GeofenceResource($geofence);
    }

    public function destroy(Geofence $geofence): JsonResponse
    {
        $this->authorize('delete', $geofence);

        $geofence->delete();

        return response()->json(['success' => true]);
    }

    public function duplicate(Request $request, Geofence $geofence): GeofenceResource
    {
        $this->authorize('create', Geofence::class);

        return new GeofenceResource($this->geofenceService->duplicate($geofence->load('points'), $request->user()));
    }

    public function activate(Request $request, Geofence $geofence): GeofenceResource
    {
        $this->authorize('update', $geofence);

        return new GeofenceResource($this->geofenceService->setStatus($geofence, GeofenceStatus::Active, $request->user()));
    }

    public function deactivate(Request $request, Geofence $geofence): GeofenceResource
    {
        $this->authorize('update', $geofence);

        return new GeofenceResource($this->geofenceService->setStatus($geofence, GeofenceStatus::Inactive, $request->user()));
    }

    public function archive(Request $request, Geofence $geofence): GeofenceResource
    {
        $this->authorize('update', $geofence);

        return new GeofenceResource($this->geofenceService->setStatus($geofence, GeofenceStatus::Archived, $request->user()));
    }

    public function restore(Request $request, Geofence $geofence): GeofenceResource
    {
        $this->authorize('update', $geofence);

        return new GeofenceResource($this->geofenceService->setStatus($geofence, GeofenceStatus::Active, $request->user()));
    }

    public function export(): JsonResponse
    {
        $this->authorize('viewAny', Geofence::class);

        $geofences = Geofence::query()->with('points')->get();

        return response()->json(['geofences' => GeofenceResource::collection($geofences)])
            ->header('Content-Disposition', 'attachment; filename="geofences-export.json"');
    }

    public function import(Request $request): AnonymousResourceCollection
    {
        $this->authorize('create', Geofence::class);

        $data = $request->validate([
            'geofences' => ['required', 'array', 'min:1'],
            'geofences.*.name' => ['required', 'string', 'max:255'],
            'geofences.*.type' => ['required', 'string'],
            'geofences.*.category' => ['required', 'string'],
        ]);

        try {
            $imported = $this->geofenceService->import($data['geofences'], $request->user());
        } catch (\Throwable) {
            throw ValidationException::withMessages(['geofences' => 'One or more geofences in the import file are invalid.']);
        }

        return GeofenceResource::collection(collect($imported));
    }

    public function events(Request $request, Geofence $geofence): AnonymousResourceCollection
    {
        $this->authorize('view', $geofence);

        $query = $geofence->events()->with(['geofence', 'user']);

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->date('from')) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->where('occurred_at', '<=', $to);
        }

        $events = $query->orderByDesc('occurred_at')->paginate(50);

        return GeofenceEventResource::collection($events);
    }
}
