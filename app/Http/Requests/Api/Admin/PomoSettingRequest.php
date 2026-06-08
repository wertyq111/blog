<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class PomoSettingRequest extends FormRequest
{
    /**
     * 番茄钟设置接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function rules(): array
    {
        $method = $this->route()->getActionMethod();

        return match ($method) {
            'save' => [
                'focus_min' => ['required', 'integer', 'min:1', 'max:600'],
                'short_break_min' => ['required', 'integer', 'min:1', 'max:600'],
                'long_break_min' => ['required', 'integer', 'min:1', 'max:600'],
                'long_break_every' => ['required', 'integer', 'min:1', 'max:99'],
                'auto_start_next' => ['required', 'boolean'],
                'sound_on' => ['required', 'boolean'],
                'white_noise' => ['nullable', 'string', 'in:rain,forest,wave'],
                'white_noise_volume' => ['required', 'numeric', 'min:0', 'max:1'],
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
            'focus_min' => '专注时长',
            'short_break_min' => '短休时长',
            'long_break_min' => '长休时长',
            'long_break_every' => '长休间隔',
            'auto_start_next' => '自动进入下一阶段',
            'sound_on' => '提示音',
            'white_noise' => '白噪音',
            'white_noise_volume' => '白噪音音量',
        ];
    }
}
