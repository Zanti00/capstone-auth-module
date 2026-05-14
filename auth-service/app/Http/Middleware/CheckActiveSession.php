<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class CheckActiveSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionId = $request->cookie('session_id') ?? $request->header('X-Session-ID');

        if (!$sessionId) {
            return response()->json(['message' => 'Unauthenticated or session missing.'], 401);
        }

        $session = DB::table('user_sessions')
            ->where('session_id', $sessionId)
            ->first();

        if (!$session || !$session->is_active) {
            return response()->json(['message' => 'Session is inactive or invalid.'], 401);
        }

        $user = $request->user();

        if (!$user || !$user->is_active) {
            return response()->json(['message' => 'User account is inactive.'], 401);
        }

        return $next($request);
    }
}
