<?php

namespace App\Http\Requests\Api\Web;

use App\Http\Requests\Api\FormRequest;
use Illuminate\Validation\Rule;

class LabelRequest extends FormRequest
{
    /**
     * @return array|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 09:35
     */
    public function rules()
    {
        switch($this->method()) {
            case 'POST':
                return [
                    'categoryId' => [
                        'required',
                        'int',
                        Rule::exists('categories', 'id')->where(function ($query) {
                            $query->where('deleted_at', '=', 0);
                        }),
                    ],
                    'name' => [
                        'required',
                        'string',
                        'min:1',
                        Rule::unique('labels')->where(function ($query) {
                            $query->where('deleted_at', '=', 0)
                                ->where('category_id', '=', $this->request->get('categoryId'));
                        }),
                    ],
                    'description' => 'required|string|min:1'
                ];
                break;
            case 'PATCH':
                return [
                    'categoryId' => [
                        'int',
                        Rule::exists('categories', 'id')->where(function ($query) {
                            $query->where('deleted_at', '=', 0);
                        }),
                    ],
                    'name' => [
                        'string',
                        'min:1',
                        Rule::unique('labels')->where(function ($query) {
                            $query->where('deleted_at', '=', 0)
                                ->where('category_id', '=', $this->request->get('categoryId'));
                        }),
                    ],
                    'description' => 'string|min:1'
                ];
                break;
        }
    }

    public function attributes()
    {
        return [
            'categoryId' => '分类',
            'name' => '标签名',
            'description' => '标签描述'
        ];
    }
}
