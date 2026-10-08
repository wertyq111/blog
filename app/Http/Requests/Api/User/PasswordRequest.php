<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;

class PasswordRequest extends FormRequest
{
    /**
     * 获取修改密码校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/10/8
     */
    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string', 'current_password:api'],
            'password' => ['required', 'string', 'min:6', 'max:32', 'confirmed', 'different:old_password'],
        ];
    }

    /**
     * 获取修改密码字段名称。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/10/8
     */
    public function attributes(): array
    {
        return [
            'old_password' => '当前密码',
            'password' => '新密码',
        ];
    }

    /**
     * 获取修改密码校验提示。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/10/8
     */
    public function messages(): array
    {
        return [
            'old_password.current_password' => '当前密码不正确',
            'password.confirmed' => '两次输入的新密码不一致',
            'password.different' => '新密码不能与当前密码相同',
        ];
    }
}
