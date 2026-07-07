<?php

use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Config::set('app.url', 'http://10.10.9.184:3925');
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    Schema::dropAllTables();
    avatarUploadCreateSchema();
    $this->avatarUploadFiles = [];
});

afterEach(function () {
    foreach ($this->avatarUploadFiles as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

it('上传并裁剪 PNG 头像到本地公开目录', function () {
    $token = avatarUploadLoginToken();
    DB::table('members')->insert([
        'user_id' => '1',
        'avatar' => 'https://cdn.example.com/legacy-avatar.png',
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Host', 'host.docker.internal:3925')
        ->post('/api/user/avatar', [
            'file' => UploadedFile::fake()->image('avatar.png', 240, 135),
            'crop_x' => 52,
            'crop_y' => 0,
            'crop_size' => 135,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('code', 0);

    $relativePath = DB::table('members')->value('avatar');
    $absolutePath = public_path($relativePath);
    $this->avatarUploadFiles[] = $absolutePath;
    [$width, $height] = getimagesize($absolutePath);

    expect($relativePath)->toStartWith('/uploads/avatars/'.date('Ymd').'/')
        ->and($response->json('data.member.avatar'))->toBe('http://10.10.9.184:3925'.$relativePath)
        ->and($width)->toBe(512)
        ->and($height)->toBe(512);
});

it('个人资料接口使用 APP_URL 返回本地头像公开地址', function () {
    $token = avatarUploadLoginToken();
    DB::table('members')->insert([
        'user_id' => '1',
        'avatar' => '/uploads/avatars/20260707/profile.gif',
    ]);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Host', 'host.docker.internal:3925')
        ->get('/api/users/getUserInfo')
        ->assertOk()
        ->assertJsonPath(
            'data.member.avatar',
            'http://10.10.9.184:3925/uploads/avatars/20260707/profile.gif'
        );
});

it('裁剪 GIF 后仍保留动画帧', function () {
    $token = avatarUploadLoginToken();
    $fixture = avatarUploadAnimatedGifFixture();
    $this->avatarUploadFiles[] = $fixture;

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->post('/api/user/avatar', [
            'file' => new UploadedFile($fixture, 'avatar.gif', 'image/gif', null, true),
            'crop_x' => 0,
            'crop_y' => 0,
            'crop_size' => 120,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('code', 0);

    $relativePath = DB::table('members')->value('avatar');
    $absolutePath = public_path($relativePath);
    $this->avatarUploadFiles[] = $absolutePath;
    $identify = new Process(['identify', '-format', '%n\n', $absolutePath]);
    $identify->mustRun();
    $frames = (int) strtok(trim($identify->getOutput()), "\n");
    [$width, $height] = getimagesize($absolutePath);

    expect($relativePath)->toEndWith('.gif')
        ->and($frames)->toBeGreaterThan(1)
        ->and($width)->toBe(120)
        ->and($height)->toBe(120);
});

it('超过最大尺寸的 GIF 裁剪结果缩小到 512', function () {
    $token = avatarUploadLoginToken();
    $fixture = avatarUploadAnimatedGifFixture(640, 640);
    $this->avatarUploadFiles[] = $fixture;

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->post('/api/user/avatar', [
            'file' => new UploadedFile($fixture, 'large-avatar.gif', 'image/gif', null, true),
            'crop_x' => 20,
            'crop_y' => 20,
            'crop_size' => 600,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('code', 0);

    $relativePath = DB::table('members')->value('avatar');
    $absolutePath = public_path($relativePath);
    $this->avatarUploadFiles[] = $absolutePath;
    [$width, $height] = getimagesize($absolutePath);

    expect($width)->toBe(512)
        ->and($height)->toBe(512);
});

it('拒绝越界的头像裁剪区域', function () {
    $token = avatarUploadLoginToken();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->post('/api/user/avatar', [
            'file' => UploadedFile::fake()->image('avatar.png', 100, 100),
            'crop_x' => 60,
            'crop_y' => 0,
            'crop_size' => 80,
        ])
        ->assertOk()
        ->assertJsonPath('code', 422)
        ->assertJsonPath('msg', '头像裁剪区域超出原图范围');

    expect(DB::table('members')->count())->toBe(0);
});

/**
 * 创建头像上传测试表结构。
 *
 * @return void
 * @author zhouxufeng <zxf@netsun.com>
 *
 * @date 2026/7/7
 */
function avatarUploadCreateSchema(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('username')->nullable();
        $table->string('email')->nullable()->unique();
        $table->string('phone')->nullable()->unique();
        $table->string('openid')->nullable()->unique();
        $table->string('unionid')->nullable()->unique();
        $table->string('password')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->integer('status')->default(0);
        $table->rememberToken();
        $table->unsignedInteger('created_at')->default(0);
        $table->integer('create_user')->default(0);
        $table->integer('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });

    Schema::create('members', function (Blueprint $table) {
        $table->id();
        $table->string('user_id', 50)->nullable();
        $table->smallInteger('member_level')->default(0);
        $table->string('realname', 50)->nullable();
        $table->string('nickname', 50)->nullable();
        $table->tinyInteger('gender')->default(3);
        $table->string('avatar', 180)->default('');
        $table->unsignedInteger('birthday')->default(0);
        $table->string('province_code', 30)->nullable();
        $table->string('city_code', 30)->nullable();
        $table->string('district_code', 30)->nullable();
        $table->string('address')->nullable();
        $table->text('intro')->nullable();
        $table->string('signature', 30)->nullable();
        $table->string('admire')->nullable();
        $table->boolean('device')->default(0);
        $table->string('device_code', 40)->nullable();
        $table->string('push_alias', 40)->default('');
        $table->boolean('source')->default(1);
        $table->boolean('status')->default(1);
        $table->string('app_version', 30)->default('');
        $table->string('code', 10)->nullable();
        $table->string('login_ip', 30)->nullable();
        $table->unsignedInteger('login_at')->default(0);
        $table->string('login_region', 20)->nullable();
        $table->unsignedInteger('login_count')->default(0);
        $table->integer('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->integer('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('code')->nullable();
        $table->unsignedInteger('deleted_at')->default(0);
    });

    Schema::create('menus', function (Blueprint $table) {
        $table->id();
        $table->string('permission')->nullable();
        $table->unsignedInteger('deleted_at')->default(0);
    });

    Schema::create('user_role', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id')->default(0);
        $table->unsignedBigInteger('role_id')->default(0);
    });

    Schema::create('role_menu', function (Blueprint $table) {
        $table->unsignedBigInteger('role_id')->default(0);
        $table->unsignedBigInteger('menu_id')->default(0);
    });
}

/**
 * 创建头像上传测试用户并返回 Token。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 *
 * @date 2026/7/7
 */
function avatarUploadLoginToken(): string
{
    $user = User::query()->create([
        'username' => 'avatar_upload_user',
        'email' => 'avatar-upload@example.com',
        'phone' => '13800000001',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);

    return auth('api')->login($user);
}

/**
 * 创建两帧 GIF 测试文件。
 *
 * @param int $width
 * @param int $height
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 *
 * @date 2026/7/7
 */
function avatarUploadAnimatedGifFixture(int $width = 160, int $height = 120): string
{
    $directory = storage_path('app/avatar-test');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $prefix = $directory.'/'.uniqid('avatar-', true);
    $firstPath = $prefix.'-1.png';
    $secondPath = $prefix.'-2.png';
    $gifPath = $prefix.'.gif';

    foreach ([[$firstPath, [32, 201, 178]], [$secondPath, [214, 255, 114]]] as [$path, $rgb]) {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, ...$rgb);
        imagefill($image, 0, 0, $color);
        imagepng($image, $path);
        imagedestroy($image);
    }

    (new Process(['convert', '-delay', '8', '-loop', '0', $firstPath, $secondPath, $gifPath]))->mustRun();
    unlink($firstPath);
    unlink($secondPath);

    return $gifPath;
}
