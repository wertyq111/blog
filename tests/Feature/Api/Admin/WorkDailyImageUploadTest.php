<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WorkDailyImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private array $uploadedPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->uploadedPaths as $path) {
            $absolutePath = public_path($path);
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }

        parent::tearDown();
    }

    public function test_work_daily_image_can_be_uploaded_to_public_work_daily_directory(): void
    {
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
        $this->uploadedPaths[] = $path;

        $response
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertStringStartsWith('/uploads/work-daily/' . date('Ymd') . '/', $path);
        $this->assertStringContainsString('/uploads/work-daily/' . date('Ymd') . '/', $response->json('data.url'));
        $this->assertFileExists(public_path($path));
    }
}
