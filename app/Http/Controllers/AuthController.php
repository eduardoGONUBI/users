<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
{
    $validatedData = $request->validate([
        'name'                  => 'required|string|max:255',
        'email'                 => 'required|string|email|max:255|unique:users',
        'password'              => 'required|string|min:6|confirmed',
        'sport'                 => 'nullable|string|max:255',
    ]);

    $user = User::create([
        'name'     => $validatedData['name'],
        'email'    => $validatedData['email'],
        'password' => bcrypt($validatedData['password']),
        'sport'    => $validatedData['sport'] ?? null,
    ]);

    // Send email verification notification
    $user->sendEmailVerificationNotification();

    return response()->json([
        'message' => 'User registered successfully. Please check your email for verification instructions.',
    ], 201);
}



    // User login
    public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (!$token = auth()->attempt($credentials)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Check if the user's email is verified
    if (!auth()->user()->hasVerifiedEmail()) {
        return response()->json(['error' => 'Email not verified'], 403);
    }

    return $this->respondWithToken($token);
}


    // Get the authenticated user
    public function me()
    {
        return response()->json(JWTAuth::user());
    }

    // Logout user
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }

    // Delete user account
    public function delete()
    {
        $user = JWTAuth::user();
        JWTAuth::invalidate(JWTAuth::getToken());
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    // Helper function to respond with token
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function updateSport(Request $request)
{
    // Validate the 'sport' input
    $validatedData = $request->validate([
        'sport' => 'required|string|max:255',
    ]);

    // Get the authenticated user
    $user = auth()->user();

    // Update the 'sport' field
    $user->sport = $validatedData['sport'];
    $user->save();

    // Return a response
    return response()->json([
        'message' => 'Favorite sport updated successfully',
        'sport'   => $user->sport,
    ], 200);
}
}
