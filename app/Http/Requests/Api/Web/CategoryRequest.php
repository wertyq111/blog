<?php

namespace App\Http\Requests\Api\Web;

use App\Http\Requests\Api\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * @return string[]|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 11:09
     */
    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
                list($class, $method) = explode('@', $this->route()->getActionName());
                if($method == 'add') {
                    return [
                        'name' => [
                            'required',
                            'string',
//                        Rule::exists('categories')->where(function ($query) {
//                            $query->where('name', '=', 'Vue');
//                        }),
                            Rule::unique('categories')->where(function ($query) {
                                $query->where('deleted_at', '=', 0);
                            }),
                        ],
                        'description' => 'string',
                        'type' => 'int',
                        'priority' => 'int'
                    ];
                } else {
                    return [
                        'name' => 'string|min:1',
                        'description' => 'string',
                        'type' => 'int',
                        'priority' => 'int'
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
            'name' => '分类名',
            'description' => '分类说明',
            'priority' => '优先级'
        ];
    }
}
