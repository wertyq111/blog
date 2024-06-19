<?php

namespace App\Services\Api\MiniProgram;

use App\Models\MiniProgram\House;
use App\Services\Api\BaseService;

class HouseService  extends BaseService
{
    public function add(House $house, array $data)
    {
        // 补充会员信息
        $data = array_merge($data, ['member_id' => auth('api')->user()->member->id]);

        $house->fill($data);
        $house->edit();

        return $house;
    }
}
