<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CaptchaRequest;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Support\Str;

class CaptchasController extends Controller
{
    /**
     * 生成图片验证码（动森木板风：深胡桃木纹底 + 奶白圆体文字 + 无干扰线）
     *
     * @param CaptchaRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/15 09:30
     */
    public function store(CaptchaRequest $request)
    {
        $key = Str::random(15);
        $cacheKey = 'captcha_' . $key;
        $phone = $request->phone;

        $captchaBuilder = new CaptchaBuilder(null, new PhraseBuilder(4));
        $captchaBuilder
            ->setBackgroundImages([resource_path('images/captcha-wood.png')]) // 动森深胡桃木板底
            ->setTextColor(247, 240, 224)         // 奶白文字 #f7f0e0，木板上清晰
            ->setMaxBehindLines(0)                // 木纹已为 OCR 制造噪声，去掉显式干扰线
            ->setMaxFrontLines(0)
            ->setDistortion(false);               // 关整图扭曲：消除背景图模式的边缘黑边，且字形更圆润不尖锐
        $captcha = $captchaBuilder->build(180, 60, resource_path('fonts/Fredoka-SemiBold.ttf'));
        $expiredAt = now()->addMinutes(2);
        \Cache::put($cacheKey, ['phone' => $phone, 'code' => $captcha->getPhrase()], $expiredAt);

        $result = [
            'code' => 0,
            'msg' => '操作成功',
            'data' => [
                'captcha_key' => $key,
                'expired_at' => $expiredAt->toDateTimeString(),
                'captcha_image_content' => $captcha->inline()
            ]
        ];

        return response()->json($result)->setStatusCode(201);
    }
}
