<?php

namespace App\Http\Resources\MiniProgram;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class WallpaperClassifyResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['updateTime'] = $this->diffDateTime($this->updated_at) ? strtotime($this->updated_at) : null;

        return $data;
    }
}
