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
    dashboardCreateSchema();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('未登录请求统计接口返回 401', function () {
    $response = $this->getJson('/api/dashboard/stats');

    $response
        ->assertStatus(200)
        ->assertJsonPath('code', 401);
});

it('非法 view 或 range 返回 422', function () {
    $token = dashboardCreateToken();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stats?view=bad')
        ->assertStatus(200)
        ->assertJsonPath('code', 422);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stats?range=bad')
        ->assertStatus(200)
        ->assertJsonPath('code', 422);
});

it('空数据用户返回空指标结构并带 cache hit', function () {
    $token = dashboardCreateToken();

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stats')
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.view', 'overview')
        ->assertJsonPath('data.metrics.total_words.value', 0)
        ->assertJsonPath('data.metrics.current_streak.value', 0)
        ->assertJsonPath('data.metrics.favorite_platform', null)
        ->assertJsonPath('data.cache_hit', false);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stats')
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.cache_hit', true);
});

it('今天没写但昨天有日志时 current streak 为 0', function () {
    // 钉死今天为周二（工作日），昨天为周一（工作日），避免工作日口径下随运行日漂移
    Carbon::setTestNow(Carbon::parse('2026-06-02 10:00:00', 'Asia/Shanghai'));

    $user = dashboardCreateUser();
    dashboardAssignSuperRole($user);

    $platformId = DB::table('work_platforms')->insertGetId([
        'name' => 'Alpha',
        'status' => 1,
        'created_at' => time(),
        'updated_at' => time(),
        'deleted_at' => 0,
    ]);

    $yesterday = Carbon::today('Asia/Shanghai')->subDay();
    DB::table('work_daily_logs')->insert([
        'platform_id' => $platformId,
        'log_date' => $yesterday->toDateString(),
        'content' => json_encode([
            'platforms' => [[
                'platform_id' => $platformId,
                'platform_name' => 'Alpha',
                'content' => '昨天产出了一些内容',
            ]],
        ], JSON_UNESCAPED_UNICODE),
        'create_user' => $user->id,
        'created_at' => $yesterday->copy()->setHour(10)->timestamp,
        'updated_at' => $yesterday->copy()->setHour(10)->timestamp,
        'deleted_at' => 0,
    ]);

    $token = auth('api')->login($user);
    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stats?range=7d')
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.metrics.current_streak.value', 0)
        ->assertJsonPath('data.metrics.current_streak.hint', '今天还没写呢');
});

it('platform view 返回排行与矩阵', function () {
    $user = dashboardCreateUser();
    dashboardAssignSuperRole($user);

    $platformId = DB::table('work_platforms')->insertGetId([
        'name' => 'Gamma',
        'status' => 1,
        'created_at' => time(),
        'updated_at' => time(),
        'deleted_at' => 0,
    ]);

    DB::table('work_daily_logs')->insert([
        'platform_id' => $platformId,
        'log_date' => now()->toDateString(),
        'content' => json_encode([
            'platforms' => [[
                'platform_id' => $platformId,
                'platform_name' => 'Gamma',
                'content' => '平台月度矩阵统计',
            ]],
        ], JSON_UNESCAPED_UNICODE),
        'create_user' => $user->id,
        'created_at' => Carbon::now('Asia/Shanghai')->setHour(10)->timestamp,
        'updated_at' => Carbon::now('Asia/Shanghai')->setHour(10)->timestamp,
        'deleted_at' => 0,
    ]);

    $token = auth('api')->login($user);
    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stats?view=platform&range=30d');

    $response
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.view', 'platform')
        ->assertJsonPath('data.rank.0.name', 'Gamma')
        ->assertJsonPath('data.matrix.rows.0.name', 'Gamma');

    expect($response->json('data.matrix.months'))->toHaveCount(12)
        ->and($response->json('data.matrix.rows.0.cells'))->toHaveCount(12)
        ->and($response->json('data.matrix.rows.0.cells.0'))->toBeInt();
});

it('overview 包含 hour dist 和 week dist', function () {
    $user = dashboardCreateUser();
    dashboardAssignSuperRole($user);

    $platformId = DB::table('work_platforms')->insertGetId([
        'name'       => 'TestPlatform',
        'status'     => 1,
        'created_at' => time(),
        'updated_at' => time(),
        'deleted_at' => 0,
    ]);

    // 在 10:00 (hour=10 CST) 写了一条日志
    $logTs = Carbon::today('Asia/Shanghai')->setHour(10)->timestamp;
    DB::table('work_daily_logs')->insert([
        'platform_id' => $platformId,
        'log_date'    => now()->toDateString(),
        'content'     => json_encode([
            'platforms' => [[
                'platform_id'   => $platformId,
                'platform_name' => 'TestPlatform',
                'content'       => str_repeat('字', 100),
            ]],
        ], JSON_UNESCAPED_UNICODE),
        'create_user' => $user->id,
        'created_at'  => $logTs,
        'updated_at'  => $logTs,
        'deleted_at'  => 0,
    ]);

    $token = auth('api')->login($user);
    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stats?view=overview&range=all');

    $response->assertOk()->assertJsonPath('code', 0);

    $hourDist = $response->json('data.metrics.hour_dist');
    $weekDist = $response->json('data.metrics.week_dist');

    expect($hourDist)->toBeArray()->toHaveCount(24)
        ->and($hourDist[10])->toEqual(100)
        ->and($weekDist)->toBeArray()->toHaveCount(7);

    $todayDow = (int) Carbon::today('Asia/Shanghai')->dayOfWeekIso - 1;
    expect($weekDist[$todayDow])->toEqual(100);
});

it('overview 包含 platform dist 和 recent logs', function () {
    $user = dashboardCreateUser();
    dashboardAssignSuperRole($user);

    $platformId = DB::table('work_platforms')->insertGetId([
        'name'       => 'BlogPlatform',
        'status'     => 1,
        'created_at' => time(),
        'updated_at' => time(),
        'deleted_at' => 0,
    ]);

    DB::table('work_daily_logs')->insert([
        'platform_id' => $platformId,
        'log_date'    => now()->toDateString(),
        'content'     => json_encode([
            'platforms' => [[
                'platform_id'   => $platformId,
                'platform_name' => 'BlogPlatform',
                'content'       => '今天写了一些内容',
            ]],
        ], JSON_UNESCAPED_UNICODE),
        'create_user' => $user->id,
        'created_at'  => time(),
        'updated_at'  => time(),
        'deleted_at'  => 0,
    ]);

    $token = auth('api')->login($user);
    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stats?view=overview&range=all');

    $response->assertOk()->assertJsonPath('code', 0);

    $dist = $response->json('data.platform_dist');
    expect($dist)->toBeArray()->not->toBeEmpty()
        ->and($dist[0]['name'])->toEqual('BlogPlatform')
        ->and($dist[0])->toHaveKeys(['words', 'pct'])
        ->and($dist[0]['pct'])->toEqual(100.0);

    $logs = $response->json('data.recent_logs');
    expect($logs)->toBeArray()->not->toBeEmpty()
        ->and($logs[0])->toHaveKeys(['log_date', 'content']);
});

/**
 * 创建仪表盘测试表结构。
 *
 * @return void
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/5/26
 */
function dashboardCreateSchema(): void
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
        $table->string('name', 50)->index();
        $table->boolean('status')->default(1);
        $table->smallInteger('sort')->default(0);
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });

    Schema::create('work_daily_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('platform_id')->default(0);
        $table->date('log_date');
        $table->text('content');
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });

    Schema::create('work_daily_tags', function (Blueprint $table) {
        $table->id();
        $table->string('name', 50);
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });

    Schema::create('work_daily_log_tag', function (Blueprint $table) {
        $table->unsignedBigInteger('work_daily_log_id')->default(0);
        $table->unsignedBigInteger('work_daily_tag_id')->default(0);
    });

    Schema::create('work_docs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('category_id')->default(0);
        $table->string('title', 255);
        $table->longText('content');
        $table->string('template_type', 50)->default('custom');
        $table->text('tags')->nullable();
        $table->unsignedTinyInteger('status')->default(1);
        $table->unsignedTinyInteger('priority')->default(0);
        $table->string('source', 255)->nullable();
        $table->unsignedTinyInteger('is_pin')->default(0);
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });
}

/**
 * 创建带超级管理员角色的登录令牌。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/5/26
 */
function dashboardCreateToken(): string
{
    $user = dashboardCreateUser();
    dashboardAssignSuperRole($user);

    return auth('api')->login($user);
}

/**
 * 创建仪表盘测试用户。
 *
 * @return User
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/5/26
 */
function dashboardCreateUser(): User
{
    static $counter = 0;
    $counter++;

    return User::query()->create([
        'username' => 'dashboard_user_' . $counter,
        'email' => "dashboard{$counter}@example.com",
        'phone' => '1380000' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
        'password' => bcrypt('password'),
        'status' => 1,
    ]);
}

/**
 * 给用户分配超级管理员角色。
 *
 * @param User $user
 * @return void
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/5/26
 */
function dashboardAssignSuperRole(User $user): void
{
    $roleId = DB::table('roles')->insertGetId([
        'name' => '超级管理员',
        'code' => 'super',
        'deleted_at' => 0,
    ]);

    DB::table('user_role')->insert([
        'user_id' => $user->id,
        'role_id' => $roleId,
    ]);
}
