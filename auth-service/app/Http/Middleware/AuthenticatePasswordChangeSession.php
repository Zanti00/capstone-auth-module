<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\SessionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePasswordChangeSession
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessionRepo,
        private readonly UserRepositoryInterface $userRepo
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $apiUser = auth('api')->user();
        if ($apiUser) {
            $request->setUserResolver(fn () => $apiUser);
            return $next($request);
        }

        $sessionId = $request->cookie('session_id') ?: $request->header('X-Session-ID');
        if (!$sessionId) {
            return response()->json(['message' => 'Unauthenticated or session missing.'], 401);
        }

        $session = $this->sessionRepo->findSessionById($sessionId);
        if (!$session || !$session->is_active) {
            return response()->json(['message' => 'Session is inactive or invalid.'], 401);
        }

        $user = $this->userRepo->findById($session->user_id, ['profile.role', 'profile.department', 'credentials']);
        if (!$user || $user->is_password_changed) {
            return response()->json(['message' => 'Unauthenticated. Please log in.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
