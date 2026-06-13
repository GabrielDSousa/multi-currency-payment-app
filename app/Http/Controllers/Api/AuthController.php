<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'country' => $data['country'],
            'currency_code' => $data['currency_code'],
            'department' => $data['department'] ?? 'employee',
        ]);

        $token = $user->createTokenWithDepartmentScope();

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => new UserResource($user),
            'token' => [
                'access_token' => $token->accessToken,
                'token_type' => 'Bearer',
                'scopes' => $token->token->scopes,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $user = Auth::user();

        $user->revokeAllTokens();

        $token = $user->createTokenWithDepartmentScope();

        return response()->json([
            'message' => 'Login successful.',
            'user' => new UserResource($user),
            'token' => [
                'access_token' => $token->accessToken,
                'token_type' => 'Bearer',
                'scopes' => $token->token->scopes,
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()->avaibleTokens()->get()->isEmpty()) {
            throw new AuthenticationException('You are logged out.');
        }

        $request->user()->revokeAllTokens();

        return response()->json(['message' => 'Logged out successfully.'], 200);
    }
}
