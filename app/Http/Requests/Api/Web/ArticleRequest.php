<?php

namespace App\Http\Requests\Api\Web;


use App\Http\Requests\Api\FormRequest;

class ArticleRequest extends FormRequest
{

    /**
     * @return array|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 15:34
     */
    public function rules()
    {
        switch($this->method()) {
            case 'POST':
                list($class, $method) = explode('@', $this->route()->getActionName());
                if($method == 'add') {
                    return [
                        'categoryId' => 'required|int',
                        'labelId' => 'int',
                        'cover' => 'string',
                        'title' => 'required|string|min:1|max:100',
                        'content' => 'required|string|min:1',
                        'viewStatus' => 'required|boolean',
                        'commentStatus' => 'required|boolean',
                        'password' => 'required_if:viewStatus,false',
                    ];
                } else {
                    return [
                        'categoryId' => 'int',
                        'labelId' => 'int',
                        'cover' => 'string',
                        'title' => 'string|min:1|max:100',
                        'content' => 'string|min:1',
                        'viewStatus' => 'required|boolean',
                        'commentStatus' => 'boolean',
                        'password' => 'required_if:viewStatus,false',
                    ];
                }
        }
    }

    /**
     * @return string[]
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 11:10
     */
    public function attributes()
    {
        return [
            'viewStatus' => '是否可见',
            'commentStatus' => '是否评论',
            'title' => '标题',
            'content' => '内容',
            'categoryId' => '分类',
            'cover' => '封面'
        ];
    }
}
