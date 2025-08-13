<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'phone',
        'whatsapp',
        'gender',
        'birth_date',
        'national_id',
        'avatar',
        'preferred_language',
        'enable_voice_interface',
        'is_active',
        'is_verified',
        'phone_verified_at',
        'last_login_at',
        'last_login_ip',
        'settings',
        'wallet_balance',
        'loyalty_points',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'settings' => 'array',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'enable_voice_interface' => 'boolean',
            'wallet_balance' => 'decimal:2',
        ];
    }

    /**
     * User type constants
     */
    const TYPE_CUSTOMER = 'customer';
    const TYPE_SHOP_OWNER = 'shop_owner';
    const TYPE_TECHNICIAN = 'technician';
    const TYPE_DRIVER = 'driver';
    const TYPE_ADMIN = 'admin';

    /**
     * Relationships
     */
    public function shop()
    {
        return $this->hasOne(Shop::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Helpers
     */
    public function isShopOwner(): bool
    {
        return $this->type === self::TYPE_SHOP_OWNER;
    }

    public function isCustomer(): bool
    {
        return $this->type === self::TYPE_CUSTOMER;
    }

    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    public function getFullPhoneAttribute(): string
    {
        return '+966' . ltrim($this->phone, '0');
    }
}
