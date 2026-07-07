<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;

class AvatarUploadRequest extends FormRequest
{
    /**
     * 获取头像上传校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
            'crop_x' => ['required', 'integer', 'min:0'],
            'crop_y' => ['required', 'integer', 'min:0'],
            'crop_size' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * 获取头像上传字段名称。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    public function attributes(): array
    {
        return [
            'file' => '头像文件',
            'crop_x' => '横向裁剪位置',
            'crop_y' => '纵向裁剪位置',
            'crop_size' => '裁剪尺寸',
        ];
    }
}
