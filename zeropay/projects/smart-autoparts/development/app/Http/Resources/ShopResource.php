<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'logo' => $this->logo,
            'cover_image' => $this->cover_image,
            'commercial_register' => $this->commercial_register,
            'tax_number' => $this->tax_number,
            'address' => $this->address,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'working_hours' => $this->working_hours,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'products_count' => $this->products_count,
            'orders_count' => $this->orders_count,
            'is_verified' => $this->is_verified,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_open' => $this->isOpen(),
            'verified_at' => $this->verified_at,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}