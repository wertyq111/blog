<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;

class PhoneRule implements Rule
{
    /**
     * 判断验证规则是否通过。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return preg_match('/^1[3456789]\d{9}$/', $value) ? true : false;
    }

    /**
     * 获取验证错误消息。
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.phone_format_error');
    }
}
