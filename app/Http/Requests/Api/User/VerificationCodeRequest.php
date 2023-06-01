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
            'captcha_code' => 'required|string',
        ];
    }

     public function attributes()
     {
         return [
             'captcha_key' => '图片验证码key',
             'captcha_code' => '图片验证码',
         ];
     }
}
