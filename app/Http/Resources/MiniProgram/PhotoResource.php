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
        $convertFormat = ['heic'];

        $data = parent::toArray($request);

        $data['category'] = $this->category;

        $imageFormat = str_replace(".", "", strtolower(strrchr($this->url,'.')));
        $data['url'] = in_array($imageFormat, $convertFormat) ? $data['url']. "?imageMogr2/format/jpg" : $data['url'];

        return $data;
    }
}
