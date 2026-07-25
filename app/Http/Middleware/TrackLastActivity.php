<?php

namespace App\Http\Middleware;

use App\Models\DeviceSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user()) {
            return $response;
        }

        $request->user()->forceFill([
            'last_activity_at' => now(),
        ])->saveQuietly();

        DeviceSession::query()
            ->where('user_id', $request->user()->id)
            ->where('session_id', $request->session()->getId())
            ->update([
                'last_activity_at' => now(),
                'is_current' => true,
            ]);

        return $response;
    }
}
