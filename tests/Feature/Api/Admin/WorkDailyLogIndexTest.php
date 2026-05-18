<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkDailyLogIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->unsignedBigInteger('platform_id')->default(0)->index();
            $table->date('log_date')->index();
            $table->text('content')->nullable();
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
            $table->unsignedBigInteger('work_daily_log_id');
            $table->unsignedBigInteger('work_daily_tag_id');
        });
    }

    public function test_index_returns_tag_and_platform_name_arrays(): void
    {
        $admin = $this->createSuperAdmin();

        $platformId = DB::table('work_platforms')->insertGetId([
            'name' => '博客平台',
            'status' => 1,
            'sort' => 1,
            'create_user' => $admin->id,
            'created_at' => time(),
            'update_user' => $admin->id,
            'updated_at' => time(),
            'deleted_at' => 0,
        ]);

        $tagId = DB::table('work_daily_tags')->insertGetId([
            'name' => '重点',
            'create_user' => $admin->id,
            'created_at' => time(),
            'update_user' => $admin->id,
            'updated_at' => time(),
            'deleted_at' => 0,
        ]);

        $logId = DB::table('work_daily_logs')->insertGetId([
            'platform_id' => 0,
            'log_date' => '2026-05-15',
            'content' => json_encode([
                'platforms' => [[
                    'platform_id' => $platformId,
                    'platform_name' => '博客平台',
                    'content' => '今天写接口',
                ]],
            ], JSON_UNESCAPED_UNICODE),
            'create_user' => $admin->id,
            'created_at' => time(),
            'update_user' => $admin->id,
            'updated_at' => time(),
            'deleted_at' => 0,
        ]);

        DB::table('work_daily_log_tag')->insert([
            'work_daily_log_id' => $logId,
            'work_daily_tag_id' => $tagId,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . auth('api')->login($admin))
            ->getJson('/api/work-daily/index');

        $response
            ->assertOk()
            ->assertJsonPath('code', 0);

        $rows = $response->json('data.data');
        if (!is_array($rows)) {
            $rows = $response->json('data');
        }

        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
        $this->assertSame($logId, $rows[0]['id']);
        $this->assertSame(['重点'], $rows[0]['tagsName']);
        $this->assertSame(['博客平台'], $rows[0]['platformsName']);
    }

    public function test_index_platform_filter_still_matches_content_platform_ids(): void
    {
        $pdo = DB::connection()->getPdo();
        if (!method_exists($pdo, 'sqliteCreateFunction')) {
            $this->markTestSkipped('当前 PDO 驱动不支持 sqliteCreateFunction');
        }

        $pdo->sqliteCreateFunction('REGEXP', static function ($pattern, $value) {
            return preg_match('/' . $pattern . '/u', (string) $value) ? 1 : 0;
        }, 2);

        $admin = $this->createSuperAdmin();

        $platformId = DB::table('work_platforms')->insertGetId([
            'name' => '博客平台',
            'status' => 1,
            'sort' => 1,
            'create_user' => $admin->id,
            'created_at' => time(),
            'update_user' => $admin->id,
            'updated_at' => time(),
            'deleted_at' => 0,
        ]);

        $otherPlatformId = DB::table('work_platforms')->insertGetId([
            'name' => '其他平台',
            'status' => 1,
            'sort' => 2,
            'create_user' => $admin->id,
            'created_at' => time(),
            'update_user' => $admin->id,
            'updated_at' => time(),
            'deleted_at' => 0,
        ]);

        $matchedLogId = DB::table('work_daily_logs')->insertGetId([
            'platform_id' => 0,
            'log_date' => '2026-05-15',
            'content' => json_encode([
                'platforms' => [[
                    'platform_id' => $platformId,
                    'platform_name' => '博客平台',
                    'content' => '今天写接口',
                ]],
            ], JSON_UNESCAPED_UNICODE),
            'create_user' => $admin->id,
            'created_at' => time(),
            'update_user' => $admin->id,
            'updated_at' => time(),
            'deleted_at' => 0,
        ]);

        DB::table('work_daily_logs')->insert([
            'platform_id' => 0,
            'log_date' => '2026-05-14',
            'content' => json_encode([
                'platforms' => [[
                    'platform_id' => $otherPlatformId,
                    'platform_name' => '其他平台',
                    'content' => '不该被筛出来',
                ]],
            ], JSON_UNESCAPED_UNICODE),
            'create_user' => $admin->id,
            'created_at' => time(),
            'update_user' => $admin->id,
            'updated_at' => time(),
            'deleted_at' => 0,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . auth('api')->login($admin))
            ->getJson('/api/work-daily/index?platform_id=' . $platformId);

        $response
            ->assertOk()
            ->assertJsonPath('code', 0);

        $rows = $response->json('data.data');
        if (!is_array($rows)) {
            $rows = $response->json('data');
        }

        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
        $this->assertSame($matchedLogId, $rows[0]['id']);
    }

    private function createSuperAdmin(): User
    {
        $admin = User::query()->create([
            'username' => 'work_daily_admin',
            'email' => 'work-daily-admin@example.com',
            'phone' => '13800000000',
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

        return $admin;
    }
}
