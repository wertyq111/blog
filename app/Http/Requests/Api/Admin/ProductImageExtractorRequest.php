<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class ProductImageExtractorRequest extends FormRequest
{
    /**
     * 获取商品图片提取接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'extract' => [
                'platform' => ['required', 'string', 'max:50'],
                'url' => ['required', 'string', 'max:2048', 'url', 'starts_with:https://'],
            ],
            'download' => [
                'platform' => ['required', 'string', 'max:50'],
                'url' => ['required', 'string', 'max:2048', 'url', 'starts_with:https://'],
                'image_ids' => ['required', 'array', 'min:1'],
                'image_ids.*' => ['required', 'string', 'size:64', 'distinct'],
            ],
            default => [],
        };
    }

    /**
     * 获取商品图片提取字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function attributes(): array
    {
        return [
            'platform' => '商品平台',
            'url' => '商品地址',
            'image_ids' => '图片',
            'image_ids.*' => '图片 ID',
        ];
    }
}
