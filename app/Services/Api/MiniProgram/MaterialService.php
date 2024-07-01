<?php

namespace App\Services\Api\MiniProgram;

use App\Models\MiniProgram\Material;
use App\Models\MiniProgram\MaterialShared;
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

    /**
     * 获取共享会员
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/7/1 14:45
     */
    public function getSharedMembers()
    {
        $model = new MaterialShared();

        $sharedMembers = $model->where('shared_member_id', auth('api')->user()->member->id)->get();

        $memberIds = null;

        if($sharedMembers) {
            foreach($sharedMembers as $sharedMember) {
                $memberIds[] = $sharedMember->member_id;
            }
        }
        return $memberIds;
    }
}
