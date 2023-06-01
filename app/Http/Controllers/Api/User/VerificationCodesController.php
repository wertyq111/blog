<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\User\VerificationCodeRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodesController extends Controller
{
    public function send(VerificationCodeRequest $request, EasySms $easySms)
    {
        $captchaCacheKey = 'captcha_'. $request->get('captcha_key');
        $captchaData = \Cache::get($captchaCacheKey);

        if(!$captchaData) {
            abort(403, '图片验证码已失效');
        }

        if (!hash_equals(strtolower($captchaData['code']), strtolower($request->get('captcha_code')))) {
            \Cache::forget($captchaCacheKey);
            throw new AuthenticationException('验证码错误');
        }

        $phone = $captchaData['phone'];

        if(!app()->environment('production')) {
            $code = '1234';
        } else {
            // 生成随机4位数, 左侧补 0
            $code = str_pad(random_int(1, 9999), 4, 0, STR_PAD_LEFT);

            try {
                $result = $easySms->send($phone, [
                    'template' => config('easysms.gateways.aliyun.templates.register'),
                    'data' => [
                        'code' => $code
                    ]
                ]);
            } catch (NoGatewayAvailableException $e) {
                $message = $e->getException('aliyun')->getMessage();
                abort(500, $message ?: '短信发送异常');
            }
        }

        $key = Str::random(15);
        $cacheKey = 'verificationCode_'. $key;
        $expiredAt = now()->addMinutes(5);
        // 缓存验证码 5 分钟过期
        \Cache::put($cacheKey, ['phone' => $phone, 'code' => $code], $expiredAt);
        // 清除图片验证码缓存
        \Cache::forget($captchaCacheKey);

        return response()->json([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString()
        ])->setStatusCode(201);
    }
}
