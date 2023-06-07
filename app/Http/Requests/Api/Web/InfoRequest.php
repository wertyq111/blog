<?php

namespace App\Http\Requests\Api\Web;

use App\Http\Requests\Api\FormRequest;

class InfoRequest extends FormRequest
{
    /**
     * @return string[]|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/6 17:14
     */
    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
            case 'PATCH':
                return [
                    'webName' => 'string',
                    'webTitle' => 'string',
                    'footer' => 'string',
                    'backgroundImage' => 'string',
                    'avatar' => 'string',
                    'status' => 'int'
                ];
                break;
        }
    }

    /**
     * @return string[]
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/6 17:19
     */
    public function attributes()
    {
        return [
            'webName' => '网站名称',
            'webTitle' => '网站标题',
            'footer' => '页脚',
            'backgroundImage' => '背景',
            'avatar' => '头像',
            'status' => '状态'
        ];
    }
}
