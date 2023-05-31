<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|\JsonSerializable
     */
    public function toArray(Request $request)
    {
        // 转换为小驼峰输出
        $array = parent::toArray($request);
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
}
