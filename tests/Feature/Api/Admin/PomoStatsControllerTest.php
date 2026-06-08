<?php

use App\Models\User\User;
use Carbon\Carbon;
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

    Schema::create('pomo_sessions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('task_id')->default(0);
        $table->char('day', 10);
        $table->unsignedInteger('completed_at')->default(0);
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });

    // 钉死到固定日期：2026-06-08 星期一
    Carbon::setTestNow(Carbon::create(2026, 6, 8, 10, 0, 0));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('记录一次完成段后近7天最后一天计数为1', function () {
    $user = pomoStatsLoginUser();
    $token = auth('api')->login($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pomo/session', ['taskId' => 0])
        ->assertOk();

    $week = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pomo/stats/week')
        ->assertOk()
        ->json('data');

    expect($week)->toHaveCount(7);
    expect($week[6]['day'])->toBe('2026-06-08');
    expect($week[6]['count'])->toBe(1);
    expect($week[0]['day'])->toBe('2026-06-02');
    expect($week[0]['count'])->toBe(0);
});

it('近7天统计只统计当前用户', function () {
    DB::table('pomo_sessions')->insert([
        'task_id' => 0, 'day' => '2026-06-08', 'completed_at' => time(),
        'create_user' => 999, 'created_at' => time(), 'updated_at' => time(), 'deleted_at' => 0,
    ]);

    $user = pomoStatsLoginUser();
    $token = auth('api')->login($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pomo/session', ['taskId' => 0])
        ->assertOk();

    $week = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pomo/stats/week')
        ->assertOk()
        ->json('data');

    expect($week[6]['count'])->toBe(1); // 仅自己的 1 条，不含别人的
});

/**
 * 创建并返回普通用户。
 *
 * @return User
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/6/8
 */
function pomoStatsLoginUser(): User
{
    return User::query()->create([
        'username' => 'pomo_stats_user',
        'email' => 'pomo-stats@example.com',
        'phone' => '13800000003',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);
}
