<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;
use App\Rules\PhoneRule;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * @return array|string[]|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/8 15:38
     */
    public function rules()
    {
        $user = $this->route('user');
        switch ($this->method()) {
            case 'POST':
                list($class, $method) = explode('@', $this->route()->getActionName());
                if($method == 'add') {
                    return [
                        'username' => [
                            'required',
                            'between:3,25',
                            'regex:/^[A-Za-z0-9\-\_]+$/',
                            Rule::unique('users')->where(function ($query) {
                                $query->where('deleted_at', 0);
                            })
                        ],
                        'phone' => [
                            Rule::unique('users')->where(function ($query) {
                                $query->where('deleted_at', 0);
                            })
                        ],
                        'email' => [
                            Rule::unique('users')->where(function ($query) {
                                $query->where('deleted_at', 0);
                            })
                        ],
                        'openid' => [
                            Rule::unique('users')->where(function ($query) {
                                $query->where('deleted_at', 0);
                            })
                        ],
                        'unionid' => [
                            Rule::unique('users')->where(function ($query) {
                                $query->where('deleted_at', 0);
                            })
                        ],
                        'password' => 'required|alpha_dash|min:6'
                    ];
                } elseif($method == 'register') {
                    return [
                        'username' => 'required|between:3,25|regex:/^[A-Za-z0-9\-\_]+$/|unique:users,username',
                        'password' => 'required|alpha_dash|min:6',
                        'verification_key' => 'required|string',
                        'verification_code' => 'required|string',
                    ];
                } else {
                    return [
                        'username' => [
                            'between:3,25',
                            'regex:/^[A-Za-z0-9\-\_]+$/',
                            Rule::unique('users')->where(function ($query) use ($user) {
                                $query->where([['deleted_at', 0], ['id', '!=', $user->id]]);
                            })
                        ]
                    ];
                }
            case 'PATCH':
                return [
                    'status' => 'boolean',
                ];
        }
    }

    public function attributes()
    {
        return [
            'verification_key' => '短信验证码必要字段',
            'verification_code' => '短信验证码',
            'phone.mobile'=>'电话格式不对',
        ];
    }
}
