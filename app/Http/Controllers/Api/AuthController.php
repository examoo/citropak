<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Load Order Booker profile
        $orderBooker = $user->orderBooker; // Assuming relationship exists

        if (!$orderBooker) {
            return response()->json([
                'message' => 'User is not an Order Booker.',
            ], 403);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'order_booker' => $orderBooker,
        ]);
    }

    public function user(Request $request)
    {
        return $request->user()->load('orderBooker');
    }

    public function logout(Request $request) 
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }
}
