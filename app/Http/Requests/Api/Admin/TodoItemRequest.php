<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class TodoItemRequest extends FormRequest
{
    /**
     * 获取待办接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'status' => ['nullable', 'integer', 'between:0,3'],
                'priority' => ['nullable', 'integer', 'between:0,3'],
                'platform_id' => ['nullable', 'integer', 'min:0'],
                'keyword' => ['nullable', 'string', 'max:255'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date'],
                'filter' => ['nullable', 'array'],
                'filter.status' => ['nullable', 'integer', 'between:0,3'],
                'filter.priority' => ['nullable', 'integer', 'between:0,3'],
                'filter.platform_id' => ['nullable', 'integer', 'min:0'],
            ]),
            'add', 'edit' => [
                'title' => ['required', 'string', 'max:255'],
                'content' => ['nullable', 'string'],
                'status' => ['nullable', 'integer', 'between:0,3'],
                'priority' => ['nullable', 'integer', 'between:0,3'],
                'due_date' => ['nullable', 'date'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string', 'max:50'],
                'platform_id' => ['nullable', 'integer', 'min:0'],
            ],
            'updateStatus' => [
                'status' => ['required', 'integer', 'between:0,3'],
            ],
            default => [],
        };
    }

    /**
     * 获取待办字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'title' => '标题',
            'content' => '描述内容',
            'status' => '状态',
            'priority' => '优先级',
            'due_date' => '截止日期',
            'tags' => '标签',
            'platform_id' => '关联工作平台',
            'keyword' => '关键词',
            'start_date' => '开始日期',
            'end_date' => '结束日期',
        ]);
    }
}
