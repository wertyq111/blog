<?php

namespace App\Http\Resources\Permission;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class MenuResource extends BaseResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['children'] = $this->whenLoaded('children');

        return $data;
    }
}
