<?php

namespace App\Services\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Utils;

class BaseService
{
    /**
     * 批量转换下级子类键名
     *
     * @param $data
     * @return mixed
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 13:07
     */
    public function convertChildrenKey($data)
    {
        foreach($data as $value) {
            $value->children = $value->menuChildren;
            if($value->children) {
                $value->children = $this->convertChildrenKey($value->children)->toArray();
            } else {
                $value->children = [];
            }
        }

//        foreach($data as &$value) {
//            $value['children'] = $value['menu_children'];
//            unset($value['menu_children']);
//            if(count($value['children']) > 0) {
//                $value['children'] = $this->convertChildrenKey($value['children']);
//            } else {
//                $value['children'] = [];
//            }
//        }

        return $data;
    }

    /**
     * 批量删除子级
     *
     * @param $children
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 13:22
     */
    public function batchDeleteChildren($children)
    {
        foreach($children as &$child) {
            if(count($child->children) > 0) {
                $this->batchDeleteChildren($child->children);
            } else {
                $child->delete();
            }
        }
        $children->each->delete();
    }

    /**
     * 发送请求
     * @param $type
     * @param $params
     * @return array|bool|float|int|object|string|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/5/17 14:59
     */
    public function sendGaode($type, $params)
    {
        // 请求 url
        $url = null;

        // 网关信息
        $params['key'] = config('weather.amap.key');

        switch($type) {
            case 'ip':
                $url = config('weather.amap.ip_position');
                break;
            case 'weather':
                $url = config('weather.amap.weather_info');
                break;
        }

        $client = new Client();
        $res = $client->get($url, [
            'query' => $params
        ]);

        $responseData = Utils::jsonDecode($res->getBody(), true);

        if($responseData['status']) {
            return $responseData;
        } else {
            throw new \Exception($responseData['info']);
        }
    }

    /**
     * 获取表格列
     *
     * @param $target
     * @return array|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/9/15 16:10
     */
    public function getColumns($target, $customList = null)
    {
        $columns = config("tablecolumns.{$target}");

        // 判断列是否需要组装
        if(isset($columns['assemble'])) {
            $assembleColumns = [];
            foreach($columns['assemble'] as $part) {
                if(is_array($part)) {
                    $assembleColumns = array_merge($assembleColumns, $part);
                } elseif ($part == 'custom' && $customList) {
                    foreach($customList as $key => $value) {
                        // 已停用的存货自定义默认隐藏
                        $assembleColumns[] = [
                            'prop' => $key,
                            'label' => $value,
                            'align' => 'center',
                            'showOverflowTooltip' => true,
                            'minWidth' => 80,
                            'isStatistic' => true
                        ];
                    }
                }
            }
            $columns = $assembleColumns;
        }

        return $columns ?? [];
    }

    /**
     * 获取档位列表
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/27 10:53
     */
    public function getStages()
    {
        $stages = [];
        $list = $this->stageModel->get()->toArray();
        foreach ($list as $l) {
            $stages['stage' . $l['id']] = $l['name'];
        }

        return $stages;
    }

    /**
     * 获取档位信息
     *
     * @param $id
     * @return null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/27 14:17
     */
    public function getStage($id)
    {
        $stage = $this->stageModel->find($id);

        return $stage ? $stage->toArray() : null;
    }

    /**
     * 获取客户信息
     *
     * @param $id
     * @return null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/27 14:17
     */
    public function getCustomer($id)
    {
        $stage = $this->stageModel->find($id);

        return $stage ? $stage->toArray() : null;
    }
}
