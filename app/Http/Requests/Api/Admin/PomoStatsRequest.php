<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class PomoStatsRequest extends FormRequest
{
    /**
     * 番茄钟统计/完成段接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function rules(): array
    {
        $method = $this->route()->getActionMethod();

        return match ($method) {
            'storeSession' => [
                'task_id' => ['nullable', 'integer', 'min:0'],
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
        return [
            'task_id' => '关联任务',
        ];
    }
}
