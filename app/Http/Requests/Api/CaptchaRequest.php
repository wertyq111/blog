<?php

namespace App\Http\Requests\Api;

class CaptchaRequest extends FormRequest
{

    /**
     * @return string[]
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/1 11:09
     */
    public function rules()
    {
        return [
            'phone' => 'required|phone:CN,mobile|unique:users',
        ];
    }

    public function attributes()
    {
        return [
            'phone' => '手机号'
        ];
    }
}
