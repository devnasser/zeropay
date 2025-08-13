<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'gender' => $this->gender,
            'avatar' => $this->avatar,
            'preferred_language' => $this->preferred_language,
            'enable_voice_interface' => $this->enable_voice_interface,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'wallet_balance' => $this->wallet_balance,
            'loyalty_points' => $this->loyalty_points,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'created_at' => $this->created_at,
        ];
    }
}