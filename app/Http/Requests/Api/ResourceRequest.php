<?php

namespace App\Http\Requests\Api;

class ResourceRequest extends FormRequest
{
    /**
     * @return string[]|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/6 15:04
     */
    public function rules()
    {
        switch($this->method()) {
            case 'POST':
            case 'PATCH':
                return [
                    'type' => 'required|string',
                    'path' => 'required|string',
                    'size' => 'required|int',
                    'mime_type' => 'required|string',
                ];
                break;
        }
    }
}
