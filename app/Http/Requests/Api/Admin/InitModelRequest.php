<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class InitModelRequest extends FormRequest
{
    /**
     * 获取模型初始化接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'code' => ['nullable', 'string', 'max:255'],
                'name' => ['nullable', 'string', 'max:255'],
                'filter' => ['nullable', 'array'],
                'filter.code' => ['nullable', 'string', 'max:255'],
                'filter.name' => ['nullable', 'string', 'max:255'],
            ]),
            'add', 'edit' => [
                'code' => ['required', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'template' => ['required', 'string'],
                'tip' => ['required', 'string'],
            ],
            'convert' => [
                'columns' => ['required', 'array'],
                'columns.*' => ['required', 'string'],
            ],
            'batchDelete' => [
                'id' => ['required'],
            ],
            default => [],
        };
    }

    /**
     * 获取模型初始化字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'code' => '框架编码',
            'name' => '框架名',
            'template' => '模板内容',
            'tip' => '参考提示',
            'columns' => '字段列表',
            'id' => '记录 ID',
        ]);
    }
}
