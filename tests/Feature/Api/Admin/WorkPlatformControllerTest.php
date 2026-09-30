<?php

use App\Models\Admin\WorkPlatform;
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
        $table->string('openid')->nullable()->unique();
        $table->string('unionid')->nullable()->unique();
        $table->string('password')->nullable();
        $table->timestamp('email_verified_at')->nullable();
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

    Schema::create('work_platforms', function (Blueprint $table) {
        $table->id();
        $table->string('name', 50);
        $table->string('category', 20)->default('work');
        $table->boolean('status')->default(1);
        $table->smallInteger('sort')->default(0);
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });
});

it('新增平台时保存平台大类', function () {
    $token = workPlatformLoginAsAdmin();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/work-platform/add', [
            'name' => 'agent智能平台',
            'category' => WorkPlatform::CATEGORY_STUDY,
            'status' => 1,
            'sort' => 0,
        ])
        ->assertOk()
        ->assertJsonPath('data.category', WorkPlatform::CATEGORY_STUDY);

    expect(WorkPlatform::query()->value('category'))->toBe(WorkPlatform::CATEGORY_STUDY);
});

// 大类决定记录进工作报表还是成长记录，缺省或填错都会让记录进错报表，必须在入口拦住。
// After 中间件把校验异常包成 HTTP 200 + body code=422。
it('平台大类缺失或不在工作学习个人之内时返回 422', function (array $payload) {
    $token = workPlatformLoginAsAdmin();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/work-platform/add', array_merge(['name' => '生意云', 'status' => 1, 'sort' => 0], $payload))
        ->assertOk()
        ->assertJsonPath('code', 422);

    expect(WorkPlatform::query()->count())->toBe(0);
})->with([
    '缺失' => [[]],
    '非法值' => [['category' => 'hobby']],
]);

function workPlatformLoginAsAdmin(): string
{
    $admin = User::query()->create([
        'username' => 'work_platform_admin',
        'email' => 'work-platform-admin@example.com',
        'phone' => '13800000031',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);

    $roleId = DB::table('roles')->insertGetId([
        'name' => '超级管理员',
        'code' => 'super',
        'deleted_at' => 0,
    ]);

    DB::table('user_role')->insert([
        'user_id' => $admin->id,
        'role_id' => $roleId,
    ]);

    return auth('api')->login($admin);
}
