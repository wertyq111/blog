<?php

namespace Tests\Feature\Api\User;

use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UpdateUserInfoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::dropAllTables();
        $this->createProfileSchema();
    }

    /**
     * 当前用户资料可更新，并在会员信息缺失时创建会员。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function test_current_user_profile_can_be_updated_and_member_is_created_when_missing(): void
    {
        $user = User::query()->create([
            'username' => 'profile_user',
            'email' => 'before@example.com',
            'phone' => '13800000000',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/index/updateUserInfo', [
                'avatar' => '',
                'email' => 'after@example.com',
                'mobile' => '13900000000',
                'realname' => '测试用户',
                'nickname' => '测试昵称',
                'gender' => 2,
                'address' => '浙江杭州',
                'intro' => 'profile intro',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.email', 'after@example.com')
            ->assertJsonPath('data.phone', '13900000000')
            ->assertJsonPath('data.member.realname', '测试用户')
            ->assertJsonPath('data.member.nickname', '测试昵称');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'after@example.com',
            'phone' => '13900000000',
        ]);

        $this->assertDatabaseHas('members', [
            'user_id' => (string) $user->id,
            'realname' => '测试用户',
            'nickname' => '测试昵称',
            'gender' => 2,
            'avatar' => '',
            'address' => '浙江杭州',
            'intro' => 'profile intro',
        ]);
    }

    /**
     * 创建个人资料测试表结构。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function createProfileSchema(): void
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

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 50)->nullable();
            $table->smallInteger('member_level')->default(0);
            $table->string('realname', 50)->nullable();
            $table->string('nickname', 50)->nullable();
            $table->tinyInteger('gender')->default(3);
            $table->string('avatar', 180)->default('');
            $table->unsignedInteger('birthday')->default(0);
            $table->string('province_code', 30)->nullable();
            $table->string('city_code', 30)->nullable();
            $table->string('district_code', 30)->nullable();
            $table->string('address')->nullable();
            $table->text('intro')->nullable();
            $table->string('signature', 30)->nullable();
            $table->string('admire')->nullable();
            $table->boolean('device')->default(0);
            $table->string('device_code', 40)->nullable();
            $table->string('push_alias', 40)->default('');
            $table->boolean('source')->default(1);
            $table->boolean('status')->default(1);
            $table->string('app_version', 30)->default('');
            $table->string('code', 10)->nullable();
            $table->string('login_ip', 30)->nullable();
            $table->unsignedInteger('login_at')->default(0);
            $table->string('login_region', 20)->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->integer('create_user')->default(0);
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

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('permission')->nullable();
            $table->unsignedInteger('deleted_at')->default(0);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedBigInteger('role_id')->default(0);
        });

        Schema::create('role_menu', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->default(0);
            $table->unsignedBigInteger('menu_id')->default(0);
        });
    }
}
