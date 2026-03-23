<?php

namespace App\Services\Api;

class MenuService extends BaseService
{
    public static function permissionContrastMap(): array
    {
        return [
            1 => [
                'title' => '查询%s%',
                'permission' => 'sys:%s%:index'
            ],
            5 => [
                'title' => '添加%s%',
                'permission' => 'sys:%s%:add'
            ],
            10 => [
                'title' => '修改%s%',
                'permission' => 'sys:%s%:edit'
            ],
            15 => [
                'title' => '删除%s%',
                'permission' => 'sys:%s%:delete'
            ],
            20 => [
                'title' => '设置状态',
                'permission' => 'sys:%s%:status'
            ],
            25 => [
                'title' => '批量删除',
                'permission' => 'sys:%s%:dall'
            ],
            30 => [
                'title' => '全部展开',
                'permission' => 'sys:%s%:expand'
            ],
            35 => [
                'title' => '全部折叠',
                'permission' => 'sys:%s%:collapse'
            ],
            40 => [
                'title' => '添加子级',
                'permission' => 'sys:%s%:addz'
            ],
            45 => [
                'title' => '导出数据',
                'permission' => 'sys:%s%:export'
            ],
            50 => [
                'title' => '导入数据',
                'permission' => 'sys:%s%:import'
            ],
            55 => [
                'title' => '分配权限',
                'permission' => 'sys:%s%:permission'
            ],
            60 => [
                'title' => '重置密码',
                'permission' => 'sys:%s%:resetPwd'
            ],
            65 => [
                'title' => '状态',
                'permission' => 'sys:%s%:state'
            ]
        ];
    }
}
