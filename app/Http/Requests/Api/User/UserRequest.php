<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * 获取用户接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'include' => ['nullable'],
                'username' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'status' => ['nullable', 'integer', 'in:0,1,2'],
                'gender' => ['nullable', 'integer', 'in:1,2,3'],
                'filter' => ['nullable', 'array'],
                'filter.username' => ['nullable', 'string', 'max:255'],
                'filter.phone' => ['nullable', 'string', 'max:255'],
                'filter.status' => ['nullable', 'integer', 'in:0,1,2'],
                'filter.gender' => ['nullable', 'integer', 'in:1,2,3'],
                'filter.roles.id' => ['nullable', 'integer', 'min:1'],
            ]),
            'add' => array_merge($this->saveRules($user), [
                'username' => [
                    'required',
                    'between:3,25',
                    'regex:/^[A-Za-z0-9\-\_]+$/',
                    Rule::unique('users')->where(function ($query) {
                        $query->where('deleted_at', 0);
                    }),
                ],
                'password' => ['required', 'alpha_dash', 'min:6'],
            ]),
            'edit' => $this->saveRules($user),
            'status' => [
                'status' => ['required', 'integer', 'in:0,1,2'],
            ],
            default => [],
        };
    }

    /**
     * 获取用户保存字段规则。
     *
     * @param mixed $user
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function saveRules($user): array
    {
        return [
            'username' => [
                'sometimes',
                'between:3,25',
                'regex:/^[A-Za-z0-9\-\_]+$/',
                Rule::unique('users')->where(function ($query) use ($user) {
                    $query->where([['deleted_at', 0], ['id', '!=', $user?->id]]);
                }),
            ],
            'phone' => [
                'nullable',
                Rule::unique('users')->where(function ($query) use ($user) {
                    $query->where([['deleted_at', 0], ['id', '!=', $user?->id]]);
                }),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->where(function ($query) use ($user) {
                    $query->where([['deleted_at', 0], ['id', '!=', $user?->id]]);
                }),
            ],
            'openid' => [
                'nullable',
                Rule::unique('users')->where(function ($query) use ($user) {
                    $query->where([['deleted_at', 0], ['id', '!=', $user?->id]]);
                }),
            ],
            'unionid' => [
                'nullable',
                Rule::unique('users')->where(function ($query) use ($user) {
                    $query->where([['deleted_at', 0], ['id', '!=', $user?->id]]);
                }),
            ],
            'password' => ['nullable', 'alpha_dash', 'min:6'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'min:1'],
        ];
    }

    /**
     * 获取用户字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes()
    {
        return array_merge($this->paginationAttributes(), [
            'verification_key' => '短信验证码必要字段',
            'verification_code' => '短信验证码',
            'phone.mobile'=>'电话格式不对',
            'username' => '用户账号',
            'password' => '密码',
            'status' => '状态',
            'role_ids' => '角色',
        ]);
    }
}
