<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class DashboardStatsRequest extends FormRequest
{
    /**
     * 获取工作台统计接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        return [
            'view' => ['nullable', 'string', 'in:overview,platform,hour,tag'],
            'range' => ['nullable', 'string', 'in:all,30d,7d,today'],
        ];
    }

    /**
     * 获取工作台统计字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return [
            'view' => '视图',
            'range' => '范围',
        ];
    }
}
