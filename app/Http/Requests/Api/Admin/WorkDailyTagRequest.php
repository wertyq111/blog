<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class WorkDailyTagRequest extends FormRequest
{
    /**
     * 获取工作日常标签接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'add' => [
                'name' => ['required', 'string', 'max:50'],
            ],
            default => [],
        };
    }

    /**
     * 获取工作日常标签字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return [
            'name' => '标签名称',
        ];
    }
}
