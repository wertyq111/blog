<?php

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(Tests\TestCase::class, RefreshDatabase::class);

afterEach(function () {
    foreach ($GLOBALS['workDailyImageUploadedPaths'] ?? [] as $path) {
        $absolutePath = public_path($path);
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    $GLOBALS['workDailyImageUploadedPaths'] = [];
});

function workDailyImageLoginToken(): string
{
    $user = User::query()->create([
        'username' => 'work_daily_image_user',
        'email' => 'work-daily-image@example.com',
        'phone' => '13800000000',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);

    return auth('api')->login($user);
}

function workDailyImageUpload(string $token, array $headers = [])
{
    return test()
        ->withHeader('Authorization', "Bearer {$token}")
        ->withHeaders($headers)
        ->post('/api/work-daily/image', [
            'file' => UploadedFile::fake()->create('daily.png', 10, 'image/png'),
        ]);
}

it('把工作日常图片上传到 public/uploads/work-daily 按日期分目录', function () {
    $response = workDailyImageUpload(workDailyImageLoginToken());

    $path = $response->json('data.path');
    $GLOBALS['workDailyImageUploadedPaths'][] = $path;

    $response
        ->assertOk()
        ->assertJsonPath('code', 0);

    expect($path)->toStartWith('/uploads/work-daily/' . date('Ymd') . '/');
    expect($response->json('data.url'))->toContain('/uploads/work-daily/' . date('Ymd') . '/');
    expect(public_path($path))->toBeFile();
});

// 前端 dev server 代理（changeOrigin）会把请求 Host 改写成容器内地址，
// 图片地址若跟着请求 Host 走，浏览器就打不开——必须始终按 APP_URL 生成。
it('图片地址按 APP_URL 生成，不受请求 Host 影响', function () {
    config(['app.url' => 'http://10.10.9.184:3925']);

    $response = workDailyImageUpload(workDailyImageLoginToken(), [
        'Host' => 'host.docker.internal:3925',
    ]);

    $path = $response->json('data.path');
    $GLOBALS['workDailyImageUploadedPaths'][] = $path;

    $response->assertOk();

    expect($response->json('data.url'))->toBe('http://10.10.9.184:3925' . $path);
});
