<?php

use App\Models\Admin\ServerPath;
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

    Schema::create('server_paths', function (Blueprint $table) {
        $table->id();
        $table->string('code')->index();
        $table->string('name')->index();
        $table->string('url', 120)->nullable();
        $table->text('target');
        $table->text('sources');
        $table->integer('sort');
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });
});

it('新增服务器路径时编码 sources', function () {
    $token = serverPathLoginAsAdmin();

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/server-path/add', [
            'code' => 'blog',
            'name' => '博客项目',
            'url' => 'https://blog.example.test',
            'target' => '/data/personal/projects/blog',
            'sources' => ['/Volumes/AgentAPFS/Program/Personal/blog'],
            'sort' => 10,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.code', 'blog')
        ->assertJsonPath('data.sort', 10);

    $serverPath = ServerPath::query()->where('code', 'blog')->firstOrFail();

    expect(json_decode($serverPath->sources, true))
        ->toBe(['/Volumes/AgentAPFS/Program/Personal/blog']);
});

it('列表接口兼容前端查询参数', function () {
    $token = serverPathLoginAsAdmin();
    serverPathCreate(['name' => '博客后端', 'sort' => 2]);
    serverPathCreate(['name' => '个人前端', 'sort' => 1]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/server-path/index?' . http_build_query([
            'page' => 1,
            'per_page' => 1,
            'name' => '博客',
        ]));

    $response
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', '博客后端')
        ->assertJsonPath('count', 1);
});

it('编辑服务器路径时不使用 Controller 兜底值', function () {
    $token = serverPathLoginAsAdmin();
    $serverPath = serverPathCreate([
        'sources' => json_encode(['/old/path']),
        'sort' => 1,
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/server-path/{$serverPath->id}", [
            'code' => 'blog-api',
            'name' => '博客后端',
            'url' => 'https://api.example.test',
            'target' => '/data/personal/projects/blog-api',
            'sources' => ['/new/path'],
            'sort' => 20,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.code', 'blog-api')
        ->assertJsonPath('data.sort', 20);

    $serverPath->refresh();

    expect(json_decode($serverPath->sources, true))
        ->toBe(['/new/path'])
        ->and($serverPath->url)
        ->toBe('https://api.example.test');
});

it('转换接口返回服务层转换结果', function () {
    $token = serverPathLoginAsAdmin();
    $serverPath = serverPathCreate([
        'target' => '/mnt/server',
        'sources' => json_encode(['C:\\project']),
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/server-path/convert/{$serverPath->id}", [
            'paths' => [
                'C:\\project\\app\\index.php',
                '/unchanged/path',
            ],
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('code', 0)
        ->assertJsonPath('data.0', '/mnt/server/app/index.php')
        ->assertJsonPath('data.1', '/unchanged/path');
});

it('批量删除接口支持多个 ID', function () {
    $token = serverPathLoginAsAdmin();
    $first = serverPathCreate(['code' => 'first']);
    $second = serverPathCreate(['code' => 'second']);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/server-path/delete', ['id' => [$first->id, $second->id]])
        ->assertOk()
        ->assertJsonPath('code', 0);

    serverPathAssertSoftDeleted($first->id);
    serverPathAssertSoftDeleted($second->id);
});

/**
 * 创建后台管理员登录令牌。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/5/26
 */
function serverPathLoginAsAdmin(): string
{
    $admin = User::query()->create([
        'username' => 'server_path_admin',
        'email' => 'server-path-admin@example.com',
        'phone' => '13800000012',
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

/**
 * 创建服务器路径测试数据。
 *
 * @param array $attributes
 * @return ServerPath
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/5/26
 */
function serverPathCreate(array $attributes = []): ServerPath
{
    return ServerPath::query()->create(array_merge([
        'code' => 'blog',
        'name' => '博客项目',
        'url' => '',
        'target' => '/data/personal/projects/blog',
        'sources' => json_encode(['/Volumes/AgentAPFS/Program/Personal/blog']),
        'sort' => 0,
        'created_at' => time(),
        'updated_at' => time(),
        'deleted_at' => 0,
    ], $attributes));
}

/**
 * 断言服务器路径已软删除。
 *
 * @param int $id
 * @return void
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/5/26
 */
function serverPathAssertSoftDeleted(int $id): void
{
    $deletedAt = DB::table('server_paths')->where('id', $id)->value('deleted_at');

    expect($deletedAt)->toBeGreaterThan(0);
}
