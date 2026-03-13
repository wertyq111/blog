<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CaptchaRequest;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Support\Str;

class CaptchasController extends Controller
{
    public function store(CaptchaRequest $request, CaptchaBuilder $captchaBuilder)
    {
        $key = Str::random(15);
        $cacheKey = 'captcha_' . $key;
        $phone = $request->phone;

        $captchaBuilder = new CaptchaBuilder(null, new PhraseBuilder(4));
        $captcha = $captchaBuilder->build();
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
