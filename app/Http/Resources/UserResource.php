<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'is_verified' => $this->is_verified,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_picture'=> $this->profile_picture,
            'role' => $this->whenLoaded('roles', fn () => RoleResource::collection($this->roles)->first()),
            'permissions' => $this->whenLoaded('permissions', fn () => PermissionResource::collection($this->permissions)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,            
        ];
    }
}
