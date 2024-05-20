<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\User\VerificationCodeRequest;
use App\Models\User\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodesController extends Controller
{
    public function send(VerificationCodeRequest $request, EasySms $easySms)
    {
        $type = $request->get('type');
        $captchaCacheKey = 'captcha_'. $request->get('captcha_key');
        $captchaData = \Cache::get($captchaCacheKey);

        if(!$captchaData) {
            abort(403, '图片验证码已失效');
        }

        if (!hash_equals(strtolower($captchaData['code']), strtolower($request->get('captcha')))) {
            \Cache::forget($captchaCacheKey);
            throw new AuthenticationException('验证码错误');
        }

        $phone = $captchaData['phone'];

        // 根据类型进行处理
        if($type == 'register') {
            // 验证手机号是否已经被注册过
            $user = User::where('phone', $phone)->first();
            if($user) {
                throw new AuthenticationException('手机号码已被注册');
            }
        }

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
        ]);
    }
}
