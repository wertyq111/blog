<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Services\Api\QiniuService;

class Controller extends BaseController
{
    public function __construct()
    {
        $this->qiniuService = new QiniuService();
    }

    /**
     * 验证是否是登录用户或增加登录用户查询条件
     *
     * @param $opponent
     * @return array|bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 14:31
     */
    public function authorizeForMember($opponent = null)
    {
        if($opponent) {
            return $opponent->member_id && $opponent->member_id == auth('api')->user()->member->id;
        } else {
            return ['member_id' => auth('api')->user()->member->id];
        }
    }
}
