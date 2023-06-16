<?php

namespace App\Http\Requests\Api\Web;

use App\Http\Requests\Api\FormRequest;

class CommentRequest extends FormRequest
{

    /**
     * @return string[]|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 15:29
     */
    public function rules()
    {
        switch($this->method()) {
            case 'POST':
                return [
                    'source' => 'required|int',
                    'type' => 'required|string',
                    'content' => 'required|string'
                ];
                break;
        }
    }

    /**
     * @return string[]
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 15:29
     */
    public function attributes()
    {
        return [
            'source' => '来源标示',
            'type' => '来源类型',
            'content' => '回复内容'
        ];
    }
}
