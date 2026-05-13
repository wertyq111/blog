<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::dropAllTables();
        $this->createDashboardSchema();
    }

    public function test_未登录请求统计接口返回_401(): void
    {
        $response = $this->getJson('/api/dashboard/stats');

        $response
            ->assertStatus(200)
            ->assertJsonPath('code', 401);
    }

    public function test_非法_view_or_range_返回_422(): void
    {
        $token = $this->createDashboardToken();

        $viewResp = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/dashboard/stats?view=bad');
        $viewResp
            ->assertStatus(200)
            ->assertJsonPath('code', 422);

        $rangeResp = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/dashboard/stats?range=bad');
        $rangeResp
            ->assertStatus(200)
            ->assertJsonPath('code', 422);
    }

    public function test_空数据用户返回空指标结构_并带_cache_hit(): void
    {
        $token = $this->createDashboardToken();

        $first = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/dashboard/stats');

        $first
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.view', 'overview')
            ->assertJsonPath('data.metrics.total_words.value', 0)
            ->assertJsonPath('data.metrics.current_streak.value', 0)
            ->assertJsonPath('data.metrics.favorite_platform', null)
            ->assertJsonPath('data.cache_hit', false);

        $second = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/dashboard/stats');

        $second
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.cache_hit', true);
    }

    public function test_今天没写但昨天有日志时_current_streak_为_0(): void
    {
        $user = $this->createDashboardUser();
        $this->assignSuperRole($user);

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
        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/dashboard/stats?range=7d');

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.metrics.current_streak.value', 0)
            ->assertJsonPath('data.metrics.current_streak.hint', '今天还没写呢');
    }

    public function test_platform_view_returns_rank_and_matrix(): void
    {
        $user = $this->createDashboardUser();
        $this->assignSuperRole($user);

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

        $this->assertCount(12, $response->json('data.matrix.months'));
    }

    private function createDashboardSchema(): void
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

    private function createDashboardToken(): string
    {
        $user = $this->createDashboardUser();
        $this->assignSuperRole($user);

        return auth('api')->login($user);
    }

    private function createDashboardUser(): User
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

    private function assignSuperRole(User $user): void
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
}
