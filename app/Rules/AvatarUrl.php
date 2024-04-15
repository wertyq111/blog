<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;

class AvatarUrl implements Rule
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
        $qiniuDomain = env('QINIU_DOMAIN', null);
        if($qiniuDomain != null && preg_match('/^(http|https):\/\/'. $qiniuDomain. '(.*)\.(jpg|jpeg|gif|webp)$/', $value)) {
            return filter_var($value, FILTER_VALIDATE_URL);
        }
        return false;
    }

    /**
     * 获取验证错误消息。
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.avatar_url');
    }
}
