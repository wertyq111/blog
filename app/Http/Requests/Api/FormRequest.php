<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Support\Str;

class FormRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 将请求参数中键值转换成下划线格式
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/6 17:11
     */
    public function getSnakeRequest()
    {
        $requestArray = $this->request->all();
        return $this->transformSnake($requestArray);
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
}
