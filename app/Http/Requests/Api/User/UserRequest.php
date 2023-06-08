<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * @return array|string[]|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/8 15:38
     */
    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
                return [
                    'username' => 'required|between:3,25|regex:/^[A-Za-z0-9\-\_]+$/|unique:users,username',
                    'password' => 'required|alpha_dash|min:6',
                    'verification_key' => 'required|string',
                    'verification_code' => 'required|string',
                ];
            case 'PATCH':
                return [
                    'status' => 'boolean',
                ];
        }
    }

    public function attributes()
    {
        return [
            'verification_key' => '短信验证码必要字段',
            'verification_code' => '短信验证码',
        ];
    }
}
