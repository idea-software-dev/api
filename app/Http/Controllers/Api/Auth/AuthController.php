<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Basic throttle on email+IP to slow brute force
        $this->ensureIsNotRateLimited($request);

        $validated = $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($this->throttleKey($request));
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $deviceName = $validated['device_name'] ?? ($request->userAgent() ?: 'NativePHP');

        // Optional: keep one token per device name
        $user->tokens()->where('name', $deviceName)->delete();

        // Issue token (abilities optional). You can pass an expiry per token:
        $expiresAt = now()->addDays(30);
        $token = $user->createToken($deviceName, ['*'], $expiresAt); // Sanctum createToken
        // ->plainTextToken returns the value your app will store.
        return response()->json([
            'token'       => $token->plainTextToken,
            'token_type'  => 'Bearer',
            'expires_at'  => $expiresAt->toIso8601String(),
            'user'        => [
                'id'    => $user->id,
                'nickname'  => $user->nickname,
                'email' => $user->email,
            ],
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        // Revoke the token used on this request
        $request->user()?->currentAccessToken()?->delete();
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', ['seconds' => $seconds]),
        ])->status(429);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }
}
