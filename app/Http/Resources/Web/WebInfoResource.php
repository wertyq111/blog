<?php

namespace App\Http\Resources\Web;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class WebInfoResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 显示隐藏值
//        $this->resource->makeVisible([
//            'created_at'
//        ]);

        return parent::toArray($request);
    }
}
