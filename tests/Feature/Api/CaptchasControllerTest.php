<?php

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

it('生成验证码返回成功响应与标准结构', function () {
    $response = $this->getJson('/api/captcha');

    $response->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonStructure([
            'data' => ['captcha_key', 'expired_at', 'captcha_image_content'],
        ]);
});

it('验证码答案写入缓存且为 4 位', function () {
    $response = $this->getJson('/api/captcha');
    $key = $response->json('data.captcha_key');

    $cached = Cache::get('captcha_' . $key);

    expect($cached)->toBeArray()
        ->and($cached['code'])->toHaveLength(4);
});

it('动森木板风：尺寸 180x60、暖木底色、木板与圆体资源就位', function () {
    $prefix = 'data:image/jpeg;base64,';
    $content = $this->getJson('/api/captcha')->json('data.captcha_image_content');

    expect($content)->toStartWith($prefix);

    $image = imagecreatefromstring(base64_decode(substr($content, strlen($prefix))));

    // 背景图尺寸即输出尺寸：木板底 180x60 生效
    expect(imagesx($image))->toBe(180)
        ->and(imagesy($image))->toBe(60);

    // 角落为暖木色调（R 明显高于 B），证明深胡桃木纹底已替换旧的纯色底。jpeg 有损，留容差。
    $rgb = imagecolorat($image, 3, 3);
    expect((($rgb >> 16) & 0xFF))->toBeGreaterThan(($rgb & 0xFF) + 8);

    // 木板与圆体资源已就位（缺失会导致 build 退化或异常）
    expect(file_exists(resource_path('images/captcha-wood.png')))->toBeTrue()
        ->and(file_exists(resource_path('fonts/Fredoka-SemiBold.ttf')))->toBeTrue();
});
