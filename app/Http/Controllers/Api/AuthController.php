<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'User account is inactive',
            ], 401);
        }

        $token = $user->createToken('inventory-system')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load('role', 'branch'),
                'token' => $token,
                'permissions' => $user->getPermissions(),
            ],
        ]);
    }

    /**
     * Get current user profile
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user();
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->load('role', 'branch'),
                'permissions' => $user->getPermissions(),
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
