<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;
use Illuminate\Validation\Rules\RequiredIf;

class AuthorizationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // 其他登录方式不需要密码
        $otherTypes = ['phone'];

        return [
            'username' => 'required|string',
            'password' => [
                new RequiredIf(!$this->type || !in_array($this->type, $otherTypes)),
                'alpha_dash'
            ],
            'captcha_key' => 'required|string',
            'captcha' => 'required|string'
        ];
    }
}
