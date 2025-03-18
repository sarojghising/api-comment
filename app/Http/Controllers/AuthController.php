<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    /**
     * Authenticate a user and generate a token.
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function login(LoginRequest $request)
    {
        if (!$this->attemptLogin($request)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $user = $this->getUserByEmail($request->email);
        $token = $this->createAuthToken($user);

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'User logged in successfully');
    }

    /**
     * Log the user out by deleting their current token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'User logged out successfully');
    }

    /**
     * Attempt to authenticate the user.
     *
     * @param LoginRequest $request
     * @return bool
     */
    private function attemptLogin(LoginRequest $request): bool
    {
        return Auth::attempt($request->only('email', 'password'));
    }

    /**
     * Get user by email.
     *
     * @param string $email
     * @return User
     */
    private function getUserByEmail(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /**
     * Create an authentication token for the user.
     *
     * @param User $user
     * @return string
     */
    private function createAuthToken(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }
}
