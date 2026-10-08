<?php

use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    Schema::dropAllTables();
    passwordCreateSchema();
});

it('当前密码正确时更新为新密码', function () {
    $token = passwordLoginToken();

    // 前端表单按小驼峰提交
    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/user/password', [
            'oldPassword' => 'old-secret',
            'password' => 'new-secret-1',
            'passwordConfirmation' => 'new-secret-1',
        ])
        ->assertOk()
        ->assertJsonPath('code', 0);

    $hash = DB::table('users')->value('password');

    expect(Hash::check('new-secret-1', $hash))->toBeTrue()
        ->and(Hash::check('old-secret', $hash))->toBeFalse()
        ->and(auth('api')->attempt(['username' => 'password_user', 'password' => 'new-secret-1']))->not->toBeFalse();
});

it('当前密码错误时拒绝修改', function () {
    $token = passwordLoginToken();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/user/password', [
            'old_password' => 'wrong-secret',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])
        ->assertOk()
        ->assertJsonPath('code', 422)
        ->assertJsonPath('msg', '当前密码不正确');

    expect(Hash::check('old-secret', DB::table('users')->value('password')))->toBeTrue();
});

it('两次输入的新密码不一致时拒绝修改', function () {
    $token = passwordLoginToken();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/user/password', [
            'old_password' => 'old-secret',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-2',
        ])
        ->assertOk()
        ->assertJsonPath('code', 422)
        ->assertJsonPath('msg', '两次输入的新密码不一致');

    expect(Hash::check('old-secret', DB::table('users')->value('password')))->toBeTrue();
});

it('新密码与当前密码相同时拒绝修改', function () {
    $token = passwordLoginToken();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/user/password', [
            'old_password' => 'old-secret',
            'password' => 'old-secret',
            'password_confirmation' => 'old-secret',
        ])
        ->assertOk()
        ->assertJsonPath('code', 422)
        ->assertJsonPath('msg', '新密码不能与当前密码相同');
});

it('新密码少于 6 位时拒绝修改', function () {
    $token = passwordLoginToken();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/user/password', [
            'old_password' => 'old-secret',
            'password' => '12345',
            'password_confirmation' => '12345',
        ])
        ->assertOk()
        ->assertJsonPath('code', 422);

    expect(Hash::check('old-secret', DB::table('users')->value('password')))->toBeTrue();
});

/**
 * 创建修改密码测试表结构。
 *
 * @return void
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/10/8
 */
function passwordCreateSchema(): void
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
}

/**
 * 创建修改密码测试用户并返回 Token。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/10/8
 */
function passwordLoginToken(): string
{
    $user = User::query()->create([
        'username' => 'password_user',
        'email' => 'password-user@example.com',
        'phone' => '13800000002',
        'password' => 'old-secret',
        'status' => 1,
    ]);

    return auth('api')->login($user);
}
