<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'regex:/^(05|5)([0-9]{8})$/', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'type' => ['required', 'in:customer,shop_owner'],
            'preferred_language' => ['required', 'in:ar,en,ur,fr,fa'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $this->formatPhone($request->phone),
            'password' => Hash::make($request->password),
            'type' => $request->type,
            'preferred_language' => $request->preferred_language,
        ]);

        // Assign role based on type
        if ($request->type === 'shop_owner') {
            $user->assignRole('shop-owner');
        } else {
            $user->assignRole('customer');
        }

        event(new Registered($user));

        Auth::login($user);

        // Redirect based on user type
        if ($user->type === 'shop_owner') {
            return redirect()->route('shop.onboarding');
        }

        return redirect(route('dashboard', absolute: false));
    }

    private function formatPhone($phone)
    {
        // Remove 0 prefix if exists
        $phone = ltrim($phone, '0');
        
        // Ensure it starts with 5
        if (!str_starts_with($phone, '5')) {
            return '5' . $phone;
        }
        
        return $phone;
    }
}