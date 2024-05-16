<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\FormRequest;
use App\Rules\PhoneRule;
use Illuminate\Validation\Rule;

class WebAuthorizationRequest extends FormRequest
{
    /**
     * @return array|array[]|string[]|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/16 16:20
     */
    public function rules()
    {
        return [
            'username' => 'required|between:3,25|regex:/^[A-Za-z0-9\-\_]+$/|unique:users,username',
            'password' => 'required|alpha_dash|min:6',
            'captcha_key' => 'required|string',
            'captcha' => 'required|string',
        ];
    }

    public function attributes()
    {
        return [
            'captcha_key' => '短信验证码必要字段',
            'captcha' => '短信验证码',
            'phone.mobile' => '电话格式不对',
        ];
    }
}
