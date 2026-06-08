<?php

use App\Models\Admin\PomoTask;
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

    Schema::create('pomo_tasks', function (Blueprint $table) {
        $table->id();
        $table->string('title', 255);
        $table->unsignedInteger('estimated_pomos')->default(1);
        $table->unsignedInteger('completed_pomos')->default(0);
        $table->unsignedTinyInteger('done')->default(0);
        $table->unsignedInteger('sort')->default(0);
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });
});

it('新增任务并出现在列表', function () {
    $user = pomoTaskLoginUser();
    $token = auth('api')->login($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pomo/task/add', [
            'title' => '写周报',
            'estimatedPomos' => 2,
        ])
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.title', '写周报');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pomo/task/index')
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.0.title', '写周报');
});

it('列表只返回当前用户任务', function () {
    DB::table('pomo_tasks')->insert([
        'title' => '别人的', 'estimated_pomos' => 1, 'completed_pomos' => 0,
        'done' => 0, 'sort' => 0, 'create_user' => 999,
        'created_at' => time(), 'updated_at' => time(), 'deleted_at' => 0,
    ]);

    $user = pomoTaskLoginUser();
    $token = auth('api')->login($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pomo/task/add', ['title' => '我的', 'estimatedPomos' => 1])
        ->assertOk();

    $data = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pomo/task/index')
        ->assertOk()
        ->json('data');

    expect(collect($data)->pluck('title'))->toContain('我的')->not->toContain('别人的');
});

it('勾选完成切换 done 状态', function () {
    $user = pomoTaskLoginUser();
    $token = auth('api')->login($user);

    $id = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pomo/task/add', ['title' => 'x', 'estimatedPomos' => 1])
        ->json('data.id');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/pomo/task/toggle-done/{$id}")
        ->assertOk()->assertJsonPath('data.done', 1);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/pomo/task/toggle-done/{$id}")
        ->assertOk()->assertJsonPath('data.done', 0);
});

it('番茄数 +1', function () {
    $user = pomoTaskLoginUser();
    $token = auth('api')->login($user);

    $id = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pomo/task/add', ['title' => 'x', 'estimatedPomos' => 1])
        ->json('data.id');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/pomo/task/increment/{$id}")
        ->assertOk()->assertJsonPath('data.completed_pomos', 1);
});

it('删除任务', function () {
    $user = pomoTaskLoginUser();
    $token = auth('api')->login($user);

    $id = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pomo/task/add', ['title' => 'x', 'estimatedPomos' => 1])
        ->json('data.id');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/pomo/task/{$id}")
        ->assertOk();

    expect(PomoTask::query()->whereKey($id)->exists())->toBeFalse();
});

/**
 * 创建并返回普通用户（无 super 角色）。
 *
 * @return User
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/6/8
 */
function pomoTaskLoginUser(): User
{
    return User::query()->create([
        'username' => 'pomo_task_user',
        'email' => 'pomo-task@example.com',
        'phone' => '13800000002',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);
}
