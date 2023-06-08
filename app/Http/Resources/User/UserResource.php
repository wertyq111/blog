<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseResource;
use App\Models\Permission\Role;
use Illuminate\Http\Request;

class UserResource extends BaseResource
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
            'openid' => $this->openid,
            'unionid' => $this->unionid,
            'phone' => (int)$this->phone,
            'status' => $this->status ? true : false,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            'member' => $this->whenLoaded('member'),
            'roles' => $this->whenLoaded('roles')
        ];
    }
}
