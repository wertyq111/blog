<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;
use App\Rules\AvatarUrl;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    /**
     * 获取会员接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'username' => ['nullable', 'string', 'max:255'],
                'gender' => ['nullable', 'integer', 'in:1,2,3'],
                'nickname' => ['nullable', 'string', 'max:50'],
                'filter' => ['nullable', 'array'],
                'filter.username' => ['nullable', 'string', 'max:255'],
                'filter.gender' => ['nullable', 'integer', 'in:1,2,3'],
                'filter.nickname' => ['nullable', 'string', 'max:50'],
            ]),
            'status' => [
                'status' => ['required', 'integer', 'in:0,1,2'],
            ],
            'add' => array_merge($this->saveRules(), [
                'user_id' => [
                    'required',
                    'integer',
                    Rule::unique('members')->where(function ($query) {
                        $query->where('deleted_at', 0);
                    }),
                ],
            ]),
            'edit' => $this->saveRules(),
            'updateAdmire' => [
                'admire' => ['required', 'decimal:0,2'],
            ],
            default => [],
        };
    }

    /**
     * 获取会员保存字段规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function saveRules(): array
    {
        return [
            'member_level' => ['nullable', 'integer', 'min:0'],
            'realname' => ['nullable', 'string', 'max:50'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'integer', 'in:1,2,3'],
            'avatar' => ['nullable', new AvatarUrl()],
            'birthday' => ['nullable'],
            'province_code' => ['nullable', 'string', 'max:30'],
            'city_code' => ['nullable', 'string', 'max:30'],
            'district_code' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'array'],
            'address' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string'],
            'signature' => ['nullable', 'string', 'max:30'],
            'admire' => ['nullable'],
            'device' => ['nullable', 'integer'],
            'device_code' => ['nullable', 'string', 'max:40'],
            'push_alias' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'integer'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'app_version' => ['nullable', 'string', 'max:30'],
            'code' => ['nullable', 'string', 'max:10'],
            'login_ip' => ['nullable', 'string', 'max:30'],
            'login_at' => ['nullable', 'integer', 'min:0'],
            'login_region' => ['nullable', 'string', 'max:20'],
            'login_count' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * 获取会员字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes()
    {
        return array_merge($this->paginationAttributes(), [
            'avatar' => '头像',
            'user_id' => '用户会员',
            'nickname' => '用户昵称',
            'gender' => '性别',
            'status' => '状态',
            'app_version' => '客户端版本号',
        ]);
    }
}
