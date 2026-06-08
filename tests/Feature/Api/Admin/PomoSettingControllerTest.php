<?php

use App\Models\Admin\PomoSetting;
use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    Schema::dropAllTables();

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('username')->nullable();
        $table->string('email')->nullable()->unique();
        $table->string('phone')->nullable()->unique();
        $table->string('password')->nullable();
        $table->integer('status')->default(0);
        $table->rememberToken();
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

    Schema::create('user_role', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id')->default(0);
        $table->unsignedBigInteger('role_id')->default(0);
    });

    Schema::create('pomo_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('create_user')->default(0)->unique();
        $table->unsignedInteger('focus_min')->default(25);
        $table->unsignedInteger('short_break_min')->default(5);
        $table->unsignedInteger('long_break_min')->default(15);
        $table->unsignedInteger('long_break_every')->default(4);
        $table->unsignedTinyInteger('auto_start_next')->default(0);
        $table->unsignedTinyInteger('sound_on')->default(1);
        $table->string('white_noise', 50)->nullable();
        $table->decimal('white_noise_volume', 3, 2)->default(0.60);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });
});

it('首次读取设置返回默认值', function () {
    $user = pomoSettingLoginUser();

    $this->withHeader('Authorization', 'Bearer ' . auth('api')->login($user))
        ->getJson('/api/pomo/setting')
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.focusMin', 25)
        ->assertJsonPath('data.longBreakEvery', 4);
});

it('保存设置后再次读取返回新值且按用户隔离', function () {
    $user = pomoSettingLoginUser();
    $token = auth('api')->login($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pomo/setting', [
            'focusMin' => 50,
            'shortBreakMin' => 10,
            'longBreakMin' => 20,
            'longBreakEvery' => 3,
            'autoStartNext' => true,
            'soundOn' => false,
            'whiteNoise' => 'rain',
            'whiteNoiseVolume' => 0.8,
        ])
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.focusMin', 50);

    expect(PomoSetting::where('create_user', $user->id)->count())->toBe(1);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pomo/setting')
        ->assertOk()
        ->assertJsonPath('data.focusMin', 50)
        ->assertJsonPath('data.whiteNoise', 'rain');
});

/**
 * 创建并登录普通用户。
 *
 * @return User
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/6/8
 */
function pomoSettingLoginUser(): User
{
    return User::query()->create([
        'username' => 'pomo_setting_user',
        'email' => 'pomo-setting@example.com',
        'phone' => '13800000001',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);
}
