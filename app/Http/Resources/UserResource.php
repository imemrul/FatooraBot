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
            'phone' => $this->phone,
            'avatar_path' => $this->avatar_path,
            'email_verified' => !is_null($this->email_verified_at),
            'is_active' => $this->is_active,
            'is_super_admin' => $this->is_super_admin,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'created_at' => $this->created_at,
        ];
    }
}
