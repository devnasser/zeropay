<?php

namespace AuthService\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use AuthService\Models\User;
use AuthService\Services\JWTService;
use AuthService\Services\BiometricService;

class AuthController extends Controller
{
    protected $jwtService;
    protected $biometricService;

    public function __construct(JWTService $jwtService, BiometricService $biometricService)
    {
        $this->jwtService = $jwtService;
        $this->biometricService = $biometricService;
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'type' => 'required|in:customer,shop_owner,technician,driver'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'type' => $validated['type']
        ]);

        // Assign role based on type
        $user->assignRole($validated['type']);

        // Generate tokens
        $tokens = $this->generateTokens($user);

        return response()->json([
            'user' => $user,
            'tokens' => $tokens
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // Check if 2FA is enabled
        if ($user->two_factor_enabled) {
            return response()->json([
                'requires_2fa' => true,
                'user_id' => $user->id
            ]);
        }

        $tokens = $this->generateTokens($user);

        return response()->json([
            'user' => $user,
            'tokens' => $tokens
        ]);
    }

    public function biometricLogin(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'biometric_data' => 'required'
        ]);

        $user = User::find($validated['user_id']);

        if (!$user || !$user->verifyBiometric($validated['biometric_data'])) {
            return response()->json(['error' => 'Invalid biometric data'], 401);
        }

        $tokens = $this->generateTokens($user);

        return response()->json([
            'user' => $user,
            'tokens' => $tokens
        ]);
    }

    private function generateTokens($user)
    {
        return [
            'access_token' => $this->jwtService->generateAccessToken($user),
            'refresh_token' => $this->jwtService->generateRefreshToken($user),
            'expires_in' => config('jwt.ttl') * 60
        ];
    }
}
