<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;

class MemberLevelRequest extends FormRequest
{
    /**
     * 获取会员等级接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'name' => ['nullable', 'string', 'max:30'],
                'filter' => ['nullable', 'array'],
                'filter.name' => ['nullable', 'string', 'max:30'],
            ]),
            'add', 'edit' => [
                'name' => ['required', 'string', 'max:30'],
                'sort' => ['nullable', 'integer', 'min:0'],
            ],
            'status' => [
                'status' => ['required', 'integer', 'in:0,1,2'],
            ],
            'batchDelete' => [
                'id' => ['required'],
            ],
            default => [],
        };
    }

    /**
     * 获取会员等级字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'name' => '级别名称',
            'sort' => '显示顺序',
            'status' => '状态',
            'id' => '记录 ID',
        ]);
    }
}
