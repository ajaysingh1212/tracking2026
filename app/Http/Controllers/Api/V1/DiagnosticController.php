<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DiagnosticEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DiagnosticEventRequest;
use App\Models\DeviceSession;
use App\Models\DiagnosticLog;
use Illuminate\Http\JsonResponse;

class DiagnosticController extends Controller
{
    public function store(DiagnosticEventRequest $request): JsonResponse
    {
        $deviceSessionId = $request->hasSession()
            ? DeviceSession::query()
                ->where('user_id', $request->user()->id)
                ->where('session_id', $request->session()->getId())
                ->value('id')
            : null;

        DiagnosticLog::create([
            'user_id' => $request->user()->id,
            'device_session_id' => $deviceSessionId,
            'event_type' => DiagnosticEventType::from($request->string('event_type')->toString()),
            'latitude' => $request->filled('latitude') ? $request->float('latitude') : null,
            'longitude' => $request->filled('longitude') ? $request->float('longitude') : null,
            'reason' => $request->string('reason')->toString() ?: null,
            'duration_seconds' => $request->filled('duration_seconds') ? $request->integer('duration_seconds') : null,
            'occurred_at' => now(),
        ]);

        return response()->json(['message' => 'Recorded.'], 201);
    }
}
