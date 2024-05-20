<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;

class VerificationCodeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules()
    {
        return [
            'captcha_key' => 'required|string',
            'captcha' => 'required|string',
        ];
    }

     public function attributes()
     {
         return [
             'captcha_key' => '图片验证码必要字段',
             'captcha' => '图片验证码',
         ];
     }
}
