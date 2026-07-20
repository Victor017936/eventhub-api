<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use LogicException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $guard = $this->guard();
        $token = $guard->login($user);

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
                'expires_in' => ($guard->getTTL() ?? 0) * 60,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $guard = $this->guard();

        $token = $guard->attempt($request->validated());

        if (! is_string($token)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful.',
            'user' => $guard->user(),
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
                'expires_in' => ($guard->getTTL() ?? 0) * 60,
            ],
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => $this->guard()->user(),
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function refresh(): JsonResponse
    {
        $guard = $this->guard();
        $token = $guard->refresh();

        return response()->json([
            'message' => 'Token refreshed successfully.',
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
                'expires_in' => ($guard->getTTL() ?? 0) * 60,
            ],
        ]);
    }

    private function guard(): JWTGuard
    {
        $guard = auth('api');

        if (! $guard instanceof JWTGuard) {
            throw new LogicException(
                'The api authentication guard must use the JWT driver.'
            );
        }

        return $guard;
    }
}
