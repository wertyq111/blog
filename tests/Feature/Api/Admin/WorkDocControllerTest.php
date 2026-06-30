<?php

use App\Models\Admin\WorkDoc;
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
        $table->integer('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });
});

it('导出 work-doc 返回带样式 HTML', function () {
    $admin = workDocLoginAsSuperAdmin();
    $doc = WorkDoc::query()->create([
        'title' => '部署手册',
        'content' => "# 部署手册\n\n## 步骤\n\n- 第一步\n- 第二步",
        'create_user' => $admin->id,
    ]);

    $response = $this
        ->withHeader('Authorization', 'Bearer ' . auth('api')->login($admin))
        ->get("/api/work-doc/{$doc->id}/export");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/html');

    $html = $response->getContent();
    expect($html)->toContain('<!DOCTYPE html>');
    expect($html)->toContain('部署手册');
    expect($html)->toContain('<h1');
    expect($html)->toContain('第一步');
});

/**
 * 创建超级管理员。
 *
 * @return User
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/6/29
 */
function workDocLoginAsSuperAdmin(): User
{
    $admin = User::query()->create([
        'username' => 'workdoc_admin',
        'email' => 'workdoc-admin@example.com',
        'phone' => '13800000001',
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
