<?php

namespace App\Services\Api\MiniProgram;

use App\Models\MiniProgram\Material;
use App\Services\Api\BaseService;

class MaterialService  extends BaseService
{
    public function add(Material $material, array $data)
    {
        // 补充会员信息
        $data = array_merge($data, ['member_id' => auth('api')->user()->member->id]);

        $material->fill($data);
        $material->edit();

        return $material;
    }
}
