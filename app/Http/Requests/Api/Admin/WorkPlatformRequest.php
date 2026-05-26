<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class WorkPlatformRequest extends FormRequest
{
    /**
     * 获取工作平台接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'name' => ['nullable', 'string', 'max:50'],
                'status' => ['nullable', 'integer', 'in:0,1'],
                'filter' => ['nullable', 'array'],
                'filter.name' => ['nullable', 'string', 'max:50'],
                'filter.status' => ['nullable', 'integer', 'in:0,1'],
            ]),
            'list' => [
                'status' => ['nullable', 'integer', 'in:0,1'],
            ],
            'add', 'edit' => [
                'name' => ['required', 'string', 'max:50'],
                'status' => ['nullable', 'integer', 'in:0,1'],
                'sort' => ['nullable', 'integer'],
            ],
            'reorder' => [
                'order' => ['required_without:list', 'array'],
                'order.*.id' => ['required_with:order', 'integer', 'min:1'],
                'order.*.sort' => ['required_with:order', 'integer'],
                'list' => ['required_without:order', 'array'],
                'list.*.id' => ['required_with:list', 'integer', 'min:1'],
                'list.*.sort' => ['required_with:list', 'integer'],
            ],
            default => [],
        };
    }

    /**
     * 获取工作平台字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'name' => '平台名称',
            'status' => '状态',
            'sort' => '排序',
            'order' => '排序数据',
            'list' => '排序数据',
        ]);
    }
}
