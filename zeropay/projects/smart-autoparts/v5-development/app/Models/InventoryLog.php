<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'reference',
        'user_id',
        'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'quantity' => 'integer'
    ];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}