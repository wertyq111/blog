<?php

namespace App\Rules;

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
        if (!is_string($value) || $value === '') {
            return false;
        }

        if($qiniuDomain != null && preg_match('/^(http|https):\/\/' . preg_quote($qiniuDomain, '/') . '(.*)\.(jpg|jpeg|gif|png|webp)$/i', $value)) {
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
