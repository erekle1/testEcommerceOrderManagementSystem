<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => $request->validated('role', 'customer'),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            [
                'user' => UserResource::make($user),
                'token' => $token,
            ],
            'User registered successfully',
            Response::HTTP_CREATED
        );
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->validated('email'))->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            [
                'user' => UserResource::make($user),
                'token' => $token,
            ],
            'Login successful'
        );
    }

    /**
     * Logout user.
     */
    public function logout(): JsonResponse
    {
        try {
            $user = request()->user();
            
            if ($user) {
                $user->currentAccessToken()?->delete();
            }

            return $this->successResponse(
                null,
                'Logout successful'
            );
        } catch (\Exception $e) {
            // Handle any authentication errors gracefully
            return $this->successResponse(
                null,
                'Logout successful'
            );
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(): JsonResponse
    {
        return $this->resourceResponse(
            UserResource::make(request()->user()),
            'User profile retrieved successfully'
        );
    }
}