<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class PomoTaskRequest extends FormRequest
{
    /**
     * 番茄钟任务接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function rules(): array
    {
        $method = $this->route()->getActionMethod();

        return match ($method) {
            'index' => array_merge($this->paginationRules(), [
                'done' => ['nullable', 'boolean'],
                'filter' => ['nullable', 'array'],
                'filter.done' => ['nullable', 'boolean'],
            ]),
            'add', 'edit' => [
                'title' => ['required', 'string', 'max:255'],
                'estimated_pomos' => ['required', 'integer', 'min:1', 'max:99'],
            ],
            'batchDelete' => [
                'id' => ['required'],
            ],
            default => [],
        };
    }

    /**
     * 字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'title' => '任务标题',
            'estimated_pomos' => '预估番茄数',
            'done' => '完成状态',
            'id' => '记录 ID',
        ]);
    }
}
