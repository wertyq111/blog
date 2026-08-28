<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class PlatformScriptRequest extends FormRequest
{
    /**
     * 获取平台脚本接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function rules(): array
    {
        $method = $this->route()->getActionMethod();

        return match ($method) {
            'index' => array_merge($this->paginationRules(), [
                'script_key' => ['nullable', 'string', 'max:64'],
                'filter' => ['nullable', 'array'],
                'filter.script_key' => ['nullable', 'string', 'max:64'],
                'filter.ordr_no' => ['nullable', 'string', 'max:40'],
                'filter.appl_id' => ['nullable', 'string', 'max:64'],
            ]),
            'preview' => [
                'script_key' => ['required', 'string', 'max:64'],
                'text' => ['nullable', 'string'],
                'login' => ['nullable', 'string', 'max:64'],
            ],
            'run' => [
                'script_key' => ['required', 'string', 'max:64'],
                'text' => ['nullable', 'string'],
                'login' => ['nullable', 'string', 'max:64'],
                'mobile' => ['nullable', 'string', 'max:20'],
                'clear_apply_no' => ['nullable', 'boolean'],
            ],
            default => [],
        };
    }

    /**
     * 获取平台脚本字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'script_key' => '脚本标识',
            'text' => '推送文本',
            'login' => '账号 (login)',
            'mobile' => '手机号码 (mobile)',
            'clear_apply_no' => '清空历史申请单号',
        ]);
    }

}
