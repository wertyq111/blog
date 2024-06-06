<?php

namespace App\Http\Resources\MiniProgram;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class PhotoResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['category'] = $this->category;

        return $data;
    }
}
