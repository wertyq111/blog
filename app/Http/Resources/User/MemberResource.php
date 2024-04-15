<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class MemberResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 显示时间
        static::showTime();

        $data = parent::toArray($request);
        $data['user'] = new UserResource($this->whenLoaded('user'));
        $city = [];
        $city[] = $this->province_code ?? "";
        $city[] = $this->city_code ?? "";
        $city[] = $this->district_code ?? "";
        $data['city'] = $city;


        return $this->transformCamel($data);
    }
}
