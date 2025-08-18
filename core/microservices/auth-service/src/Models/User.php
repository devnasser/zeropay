<?php

namespace AuthService\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'type',
        'is_verified', 'two_factor_enabled', 'biometric_data',
        'last_login', 'login_attempts', 'locked_until'
    ];

    protected $hidden = ['password', 'remember_token', 'biometric_data'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'is_verified' => 'boolean',
        'last_login' => 'datetime',
        'locked_until' => 'datetime'
    ];

    // Multi-factor authentication
    public function enableTwoFactor()
    {
        $this->two_factor_secret = encrypt(app('pragmarx.google2fa')->generateSecretKey());
        $this->two_factor_enabled = true;
        $this->save();
    }

    // Biometric authentication
    public function verifyBiometric($biometricData)
    {
        // Implement biometric verification logic
        return hash_equals($this->biometric_data, hash('sha256', $biometricData));
    }
}
