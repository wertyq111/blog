<?php

namespace Tests\Feature\Api\User;

use App\Models\Permission\Role;
use App\Models\User\Member;
use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShowUserTest extends TestCase
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
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 50)->nullable();
            $table->string('realname', 50)->nullable();
            $table->string('nickname', 50)->nullable();
            $table->tinyInteger('gender')->default(3);
            $table->string('avatar', 180)->default('');
            $table->string('address')->nullable();
            $table->text('intro')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->integer('sort')->default(0);
            $table->integer('status')->default(1);
            $table->string('note')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedBigInteger('role_id')->default(0);
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('permission')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });

        Schema::create('role_menu', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->default(0);
            $table->unsignedBigInteger('menu_id')->default(0);
        });
    }

    public function test_backend_user_detail_returns_user_member_and_roles(): void
    {
        $admin = $this->createUser('admin_user', 'admin@example.com', '13800000000');
        $targetUser = $this->createUser('detail_user', 'detail@example.com', '13800000001', [
            'username' => 'detail_user',
            'email' => 'detail@example.com',
            'status' => 1,
        ]);

        $this->createMember($targetUser);
        $role = Role::query()->create([
            'code' => 'manager',
            'name' => 'Manager',
        ]);
        $targetUser->roles()->sync([$role->id], false);
        $token = auth('api')->login($admin);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/users/{$targetUser->id}?include=member,roles");

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.username', 'detail_user')
            ->assertJsonPath('data.member.realname', '详情用户')
            ->assertJsonPath('data.roles.0.code', 'manager');
    }

    protected function createUser(string $username, string $email, string $phone, array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password' => bcrypt('password'),
            'status' => 1,
        ], $attributes));
    }

    protected function createMember(User $user): Member
    {
        return Member::query()->create([
            'user_id' => (string) $user->id,
            'realname' => '详情用户',
            'nickname' => 'detail',
            'gender' => 1,
            'avatar' => '',
            'address' => '杭州',
            'intro' => 'hello',
        ]);
    }
}
