<?php

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

$uploadedPaths = [];

afterEach(function () use (&$uploadedPaths) {
    foreach ($uploadedPaths as $path) {
        $absolutePath = public_path($path);

        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    $uploadedPaths = [];
});

it('uploads work daily images to the public work daily directory', function () use (&$uploadedPaths) {
    $user = User::query()->create([
        'username' => 'work_daily_image_user',
        'email' => 'work-daily-image@example.com',
        'phone' => '13800000000',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);

    $token = auth('api')->login($user);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->post('/api/work-daily/image', [
            'file' => UploadedFile::fake()->create('daily.png', 10, 'image/png'),
        ]);

    $path = $response->json('data.path');
    $uploadedPaths[] = $path;

    $response
        ->assertOk()
        ->assertJsonPath('code', 0);

    expect(str_starts_with($path, '/uploads/work-daily/' . date('Ymd') . '/'))
        ->toBeTrue()
        ->and(str_contains($response->json('data.url'), '/uploads/work-daily/' . date('Ymd') . '/'))
        ->toBeTrue()
        ->and(is_file(public_path($path)))
        ->toBeTrue();
});
