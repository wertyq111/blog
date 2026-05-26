<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class WorkDocCategoryRequest extends FormRequest
{
    /**
     * 获取工作文档分类接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'parent_id' => ['nullable', 'integer', 'min:0'],
                'status' => ['nullable', 'integer', 'in:0,1'],
                'filter' => ['nullable', 'array'],
                'filter.parent_id' => ['nullable', 'integer', 'min:0'],
                'filter.status' => ['nullable', 'integer', 'in:0,1'],
            ]),
            'list' => [
                'status' => ['nullable', 'integer', 'in:0,1'],
            ],
            'add', 'edit' => [
                'parent_id' => ['nullable', 'integer', 'min:0'],
                'name' => ['required', 'string', 'max:120'],
                'icon' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:255'],
                'sort' => ['nullable', 'integer', 'min:0'],
                'status' => ['nullable', 'integer', 'in:0,1'],
            ],
            'reorder' => [
                'order' => ['required_without:list', 'array'],
                'order.*.id' => ['required_with:order', 'integer', 'min:1'],
                'order.*.parent_id' => ['nullable', 'integer', 'min:0'],
                'order.*.sort' => ['nullable', 'integer', 'min:0'],
                'list' => ['required_without:order', 'array'],
                'list.*.id' => ['required_with:list', 'integer', 'min:1'],
                'list.*.parent_id' => ['nullable', 'integer', 'min:0'],
                'list.*.sort' => ['nullable', 'integer', 'min:0'],
            ],
            default => [],
        };
    }

    /**
     * 获取工作文档分类字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'parent_id' => '父级分类',
            'name' => '分类名称',
            'icon' => '分类图标',
            'description' => '分类说明',
            'sort' => '排序',
            'status' => '状态',
            'order' => '排序数据',
            'list' => '排序数据',
        ]);
    }
}
