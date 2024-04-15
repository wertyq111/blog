<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;
use App\Rules\AvatarUrl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{

    public function rules(): array
    {
        switch ($this->method()) {
            case 'POST':
                $rules = [
                    'avatar' => new AvatarUrl()
                ];
                list($class, $method) = explode('@', $this->route()->getActionName());
                if($method == 'add') {
                    $rules = array_merge($rules, [
                        'user_id' => [
                            'required',
                            'integer',
                            Rule::unique('members')->where(function ($query) {
                                $query->where('deleted_at', 0);
                            })
                        ]
                    ]);
                }

                return $rules;
            case 'PATCH':
                return [
                    'admire' => 'decimal:0,2'
                ];
            default:
                return [];
        }
    }

    public function attributes()
    {
        return [
            'avatar' => '头像',
            'user_id' => '用户会员'
        ];
    }
}
