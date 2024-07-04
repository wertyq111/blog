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
     * 增加登录用户查询条件
     *
     * @return array|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/7/4 10:17
     */
    public function authorizeForMember()
    {
        $user = auth('api')->user();

        $isManager = false;

        foreach($user->roles as $role) {
            // 超级管理员获取全部权限
            if($role->code === 'super') {
                $isManager = true;
            }
        }

        return !$isManager ? ['member_id' => $user->member->id] : null;
    }

    /**
     * 会员信息校验
     *
     * @param $model
     * @param $where
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|mixed[]|\Spatie\QueryBuilder\QueryBuilder[]
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/19 13:56
     */
    public function memberExistCheck($model, $where)
    {
        $config = [
            'conditions' => array_merge($this->authorizeForMember(), $where)
        ];

        return $this->queryBuilder($model, false, $config);
    }
}
