<?php

namespace App\Services\Api;

use App\Models\User\Member;
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
}
