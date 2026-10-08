<?php

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
    profileAbilitiesCreateSchema();
});

it('保存个人能力并在个人资料中按小驼峰返回', function () {
    $token = profileAbilitiesLoginToken();

    // 前端表单按小驼峰提交
    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/index/updateUserInfo', [
            'abilities' => [
                'position' => '资深架构师',
                'organization' => '示例科技有限公司',
                'region' => '中国 · 浙江省 · 杭州市',
                'techStack' => 'Laravel · Vue · MySQL',
                'skills' => ['Laravel', 'Vue 3', 'MySQL'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.member.abilities.position', '资深架构师')
        ->assertJsonPath('data.member.abilities.techStack', 'Laravel · Vue · MySQL')
        ->assertJsonPath('data.member.abilities.skills', ['Laravel', 'Vue 3', 'MySQL']);

    $stored = json_decode(DB::table('members')->value('abilities'), true);

    expect($stored)->toEqual([
        'position' => '资深架构师',
        'organization' => '示例科技有限公司',
        'region' => '中国 · 浙江省 · 杭州市',
        'tech_stack' => 'Laravel · Vue · MySQL',
        'skills' => ['Laravel', 'Vue 3', 'MySQL'],
    ]);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/users/getUserInfo?include=member')
        ->assertOk()
        ->assertJsonPath('data.member.abilities.organization', '示例科技有限公司');
});

it('只保存个人能力时不改动其他会员资料，也不写入未知字段', function () {
    $token = profileAbilitiesLoginToken();
    DB::table('members')->insert([
        'user_id' => '1',
        'realname' => '原姓名',
        'nickname' => '原昵称',
        'avatar' => '/uploads/avatars/20260707/keep.png',
    ]);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/index/updateUserInfo', [
            'abilities' => [
                'position' => '工程师',
                'skills' => [],
                'unknown' => '不该入库',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('code', 0);

    $member = DB::table('members')->first();

    expect($member->realname)->toBe('原姓名')
        ->and($member->nickname)->toBe('原昵称')
        ->and($member->avatar)->toBe('/uploads/avatars/20260707/keep.png')
        ->and(json_decode($member->abilities, true))->toEqual(['position' => '工程师', 'skills' => []]);
});

it('技能标签超过 20 个时拒绝保存', function () {
    $token = profileAbilitiesLoginToken();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/index/updateUserInfo', [
            'abilities' => [
                'skills' => array_map(static fn ($i) => "skill-{$i}", range(1, 21)),
            ],
        ])
        ->assertOk()
        ->assertJsonPath('code', 422);

    expect(DB::table('members')->count())->toBe(0);
});

it('不带个人能力的资料更新保持原有个人能力', function () {
    $token = profileAbilitiesLoginToken();
    DB::table('members')->insert([
        'user_id' => '1',
        'avatar' => '',
        'abilities' => json_encode(['position' => '工程师', 'skills' => ['PHP']]),
    ]);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/index/updateUserInfo', [
            'nickname' => '新昵称',
        ])
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.member.nickname', '新昵称')
        ->assertJsonPath('data.member.abilities.position', '工程师');
});

/**
 * 创建个人能力测试表结构。
 *
 * @return void
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/10/8
 */
function profileAbilitiesCreateSchema(): void
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
        $table->string('province_code', 30)->nullable();
        $table->string('city_code', 30)->nullable();
        $table->string('district_code', 30)->nullable();
        $table->string('address')->nullable();
        $table->text('intro')->nullable();
        $table->json('abilities')->nullable();
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
 * 创建个人能力测试用户并返回 Token。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/10/8
 */
function profileAbilitiesLoginToken(): string
{
    $user = User::query()->create([
        'username' => 'profile_abilities_user',
        'email' => 'profile-abilities@example.com',
        'phone' => '13800000003',
        'password' => 'password',
        'status' => 1,
    ]);

    return auth('api')->login($user);
}
