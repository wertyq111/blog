<?php

namespace Tests\Feature\Api\MiniProgram;

use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhotoCategoryAddTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nickname')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedBigInteger('role_id')->default(0);
        });

        Schema::create('photo_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('name');
            $table->unsignedBigInteger('create_user')->default(0);
            $table->unsignedBigInteger('update_user')->default(0);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });
    }

    public function test_super_admin_can_add_photo_category_without_member_scope(): void
    {
        $admin = User::query()->create([
            'username' => 'super_admin',
            'email' => 'super-admin@example.com',
            'phone' => '13800000011',
            'password' => bcrypt('admin-password'),
            'status' => 1,
        ]);

        $roleId = \DB::table('roles')->insertGetId([
            'name' => '超级管理员',
            'code' => 'super',
            'deleted_at' => 0,
        ]);

        \DB::table('user_role')->insert([
            'user_id' => $admin->id,
            'role_id' => $roleId,
        ]);

        $memberId = \DB::table('members')->insertGetId([
            'user_id' => $admin->id,
            'nickname' => 'super-member',
            'created_at' => time(),
            'updated_at' => time(),
            'deleted_at' => 0,
        ]);

        $token = auth('api')->login($admin);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/photo-categories/add', [
                'name' => '测试相册',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.name', '测试相册')
            ->assertJsonPath('data.memberId', $memberId);

        $this->assertDatabaseHas('photo_categories', [
            'name' => '测试相册',
            'member_id' => $memberId,
        ]);
    }
}
