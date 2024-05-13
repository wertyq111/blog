<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class BaseResource extends JsonResource
{
    /**
     * @var bool
     */
    public static $isShowTime = false;

    /**
     * Transform the resource into an array.
     *
     * @return array|\JsonSerializable
     */
    public function toArray(Request $request)
    {
        // 转换为小驼峰输出
        $array = parent::toArray($request);

        if(static::$isShowTime) {
            $this->resource->makeVisible(['created_at', 'updated_at', 'deleted_at']);
            $array['createTime'] = $this->diffDateTime($this->created_at) ? (string) $this->created_at : null;
            $createTime = new \DateTime($array['createTime']);
            $array['createTimestamp'] = $createTime->getTimestamp();
            $array['updateTime'] = $this->diffDateTime($this->updated_at) ? (string) $this->updated_at : null;
            $updateTime = new \DateTime($array['createTime']);
            $array['updateTimestamp'] = $updateTime->getTimestamp();
        }

        return is_array($array) ? $this->transformCamel($array) : $array;
    }

    // 小驼峰转换成下划线
    public function transformSnake(array &$array)
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $this->transformSnake($v);
            }
            unset($array[$k]);
            $array[Str::snake($k)] = $v;
        }

        return $array;
    }

    // 下划线转换成小驼峰
    public function transformCamel(array &$array)
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $this->transformCamel($v);
            }
            unset($array[$k]);
            $array[Str::camel($k)] = $v;
        }

        return $array;
    }

    /**
     * 设置请求成功时 HTTP 状态值
     * @param $request
     * @param $response
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/2 15:12
     */
    public function withResponse($request, $response)
    {
        /**
         * Not all prerequisites were met.
         */
        $response->setStatusCode(201);
    }

    /**
     * 对比时间戳是否相同
     *
     * @param $time
     * @return bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/8 11:16
     */
    public function diffDateTime($time)
    {
        return strtotime($time) > 0 && (new \DateTime($time))->getTimestamp() == strtotime($time);
    }

    /**
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 15:30
     */
    public static function showTime()
    {
        static::$isShowTime = true;
    }
}
