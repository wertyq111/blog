<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class WorkDocRequest extends FormRequest
{
    /**
     * 获取工作文档接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'category_id' => ['nullable', 'integer', 'min:0'],
                'status' => ['nullable', 'integer', 'in:0,1'],
                'template_type' => ['nullable', 'string', 'max:50'],
                'is_pin' => ['nullable', 'integer', 'in:0,1'],
                'priority' => ['nullable', 'integer', 'min:0'],
                'keyword' => ['nullable', 'string', 'max:255'],
                'content' => ['nullable', 'string', 'max:255'],
                'title' => ['nullable', 'string', 'max:255'],
                'filter' => ['nullable', 'array'],
                'filter.category_id' => ['nullable', 'integer', 'min:0'],
                'filter.status' => ['nullable', 'integer', 'in:0,1'],
                'filter.template_type' => ['nullable', 'string', 'max:50'],
                'filter.is_pin' => ['nullable', 'integer', 'in:0,1'],
                'filter.priority' => ['nullable', 'integer', 'min:0'],
            ]),
            'add', 'edit' => [
                'category_id' => ['required', 'integer', 'min:0'],
                'title' => ['required', 'string', 'max:255'],
                'content' => ['required', 'string'],
                'template_type' => ['nullable', 'string', 'max:50'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string', 'max:50'],
                'status' => ['nullable', 'integer', 'in:0,1'],
                'priority' => ['nullable', 'integer', 'min:0'],
                'source' => ['nullable', 'string', 'max:255'],
                'is_pin' => ['nullable', 'integer', 'in:0,1'],
            ],
            default => [],
        };
    }

    /**
     * 获取工作文档字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'category_id' => '文档分类',
            'title' => '标题',
            'content' => '内容',
            'template_type' => '模板类型',
            'tags' => '标签',
            'status' => '状态',
            'priority' => '优先级',
            'source' => '来源',
            'is_pin' => '是否置顶',
            'keyword' => '关键词',
        ]);
    }
}
