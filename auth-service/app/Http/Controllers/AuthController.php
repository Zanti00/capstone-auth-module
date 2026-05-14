<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\ResetPasswordRequest;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = 'login:' . Str::lower($request->username) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                'errors' => ['username' => ['Account temporarily locked.']]
            ], 429);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->credentials->password_hash)) {
            RateLimiter::hit($throttleKey, 900); // 15 minutes lockout

            DB::table('audit_logs')->insert([
                'actor_id' => $user ? $user->id : null,
                'action' => 'LOGIN_FAILED',
                'description' => 'Failed login attempt for username: ' . $request->username,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'action_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'username' => ['Invalid username or password.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Success logic
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $refreshTokenPlain = Str::random(128);
        $refreshTokenHash = hash('sha256', $refreshTokenPlain);

        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => $refreshTokenHash,
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        $sessionId = (string) Str::uuid();
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        DB::table('audit_logs')->insert([
            'actor_id' => $user->id,
            'action' => 'LOGIN_SUCCESS',
            'description' => 'Successful login for username: ' . $request->username,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'action_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'session_id' => $sessionId,
            'user' => $user
        ])->cookie(
            'refresh_token', 
            $refreshTokenPlain, 
            60 * 24 * 30, // 30 days
            null, 
            null, 
            true, // Secure
            true, // HttpOnly
            false, 
            'Strict'
        )->cookie(
            'session_id', 
            $sessionId, 
            60 * 24 * 30, // 30 days
            null, 
            null, 
            true, // Secure
            true, // HttpOnly
            false, 
            'Strict'
        );
    }

    public function refresh(Request $request)
    {
        $refreshTokenPlain = $request->cookie('refresh_token');

        if (!$refreshTokenPlain) {
            return response()->json(['message' => 'Refresh token missing.'], 401);
        }

        $tokenHash = hash('sha256', $refreshTokenPlain);

        $tokenRecord = DB::table('refresh_tokens')
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$tokenRecord || now()->greaterThan($tokenRecord->expires_at)) {
            return response()->json(['message' => 'Invalid or expired refresh token.'], 401);
        }

        // Reuse Detection
        if ($tokenRecord->is_revoked) {
            DB::table('refresh_tokens')
                ->where('user_id', $tokenRecord->user_id)
                ->update(['is_revoked' => true]);
                
            return response()->json(['message' => 'Token compromise detected. All sessions revoked.'], 401);
        }

        // Rotation: Mark old as revoked
        DB::table('refresh_tokens')
            ->where('id', $tokenRecord->id)
            ->update(['is_revoked' => true]);

        // Issue new refresh token
        $newRefreshTokenPlain = Str::random(128);
        $newRefreshTokenHash = hash('sha256', $newRefreshTokenPlain);

        DB::table('refresh_tokens')->insert([
            'user_id' => $tokenRecord->user_id,
            'token_hash' => $newRefreshTokenHash,
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        $user = User::find($tokenRecord->user_id);
        
        if (!$user || !$user->is_active) {
            return response()->json(['message' => 'User is inactive or not found.'], 401);
        }

        $accessToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'user' => $user
        ])->cookie(
            'refresh_token', 
            $newRefreshTokenPlain, 
            60 * 24 * 30,
            null, 
            null, 
            true, 
            true, 
            false, 
            'Strict'
        );
    }

    public function logout(Request $request)
    {
        $refreshTokenPlain = $request->cookie('refresh_token');

        if ($refreshTokenPlain) {
            $tokenHash = hash('sha256', $refreshTokenPlain);
            DB::table('refresh_tokens')
                ->where('token_hash', $tokenHash)
                ->update(['is_revoked' => true]);
        }

        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        $sessionId = $request->cookie('session_id') ?? $request->header('X-Session-ID');
        
        if ($sessionId) {
            DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->update(['is_active' => false]);
        }

        return response()->json(['message' => 'Successfully logged out.'])
            ->withoutCookie('refresh_token')
            ->withoutCookie('session_id');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $ip = $request->ip();
        $rateLimitKey = "pwd_reset:{$ip}";
        $windowStart = now()->subHour();

        $hits = DB::table('rate_limit_log')
            ->where('key', $rateLimitKey)
            ->where('window_start', '>=', $windowStart)
            ->count();

        if ($hits >= 3) {
            return response()->json(['message' => 'Too many password reset attempts. Please try again later.'], 429)
                ->header('Retry-After', 3600);
        }

        DB::table('rate_limit_log')->insert([
            'key' => $rateLimitKey,
            'hits' => 1,
            'window_start' => now()
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $tokenPlain = Str::random(64);
            $tokenHash = hash('sha256', $tokenPlain);

            DB::table('password_reset_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addMinutes(15),
                'created_at' => now()
            ]);

            Mail::to($user->email)->queue(new PasswordResetMail($tokenPlain));
        }

        // Always return 200 for anti-enumeration
        return response()->json(['message' => 'If an account with that email exists, a password reset link has been sent.']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $tokenHash = hash('sha256', $request->token);

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return response()->json(['message' => 'Invalid or expired password reset token.'], 400);
        }

        $user = User::find($tokenRecord->user_id);
        
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 400);
        }

        DB::transaction(function () use ($user, $tokenRecord, $request) {
            DB::table('user_credentials')
                ->where('user_id', $user->id)
                ->update([
                    'password_hash' => Hash::make($request->password, ['rounds' => 12]),
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                    'updated_at' => now()
                ]);

            DB::table('password_reset_tokens')
                ->where('id', $tokenRecord->id)
                ->update([
                    'used_at' => now(),
                    'updated_at' => now()
                ]);

            DB::table('user_sessions')
                ->where('user_id', $user->id)
                ->update(['is_active' => false]);

            DB::table('refresh_tokens')
                ->where('user_id', $user->id)
                ->update(['is_revoked' => true]);
        });

        return response()->json(['message' => 'Password has been successfully reset.']);
    }
}
