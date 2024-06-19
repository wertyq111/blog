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
     * @param $opponent
     * @return array|bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 14:31
     */
    public function authorizeForMember()
    {
        return ['member_id' => auth('api')->user()->member->id];
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
