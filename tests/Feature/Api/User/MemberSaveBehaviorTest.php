<?php

namespace Tests\Feature\Api\User;

use App\Models\User\Member;
use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberSaveBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('QINIU_DOMAIN=cdn.chouy.xyz');
        $_ENV['QINIU_DOMAIN'] = 'cdn.chouy.xyz';
        $_SERVER['QINIU_DOMAIN'] = 'cdn.chouy.xyz';

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
    }

    public function test_member_status_can_be_updated_without_triggering_avatar_validation(): void
    {
        $admin = $this->createUser('member_admin', 'admin-member@example.com', '13800000001');
        $member = $this->createMember('13800000002', '旧昵称', 'https://invalid.example.com/avatar.png');
        $token = auth('api')->login($admin);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/members/status/{$member->id}", [
                'status' => 2,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 2);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'status' => 2,
        ]);
    }

    public function test_member_edit_allows_blank_avatar_when_updating_other_fields(): void
    {
        $admin = $this->createUser('member_editor', 'editor-member@example.com', '13800000003');
        $member = $this->createMember('13800000004', '编辑前昵称', 'https://cdn.chouy.xyz/upload/original.jpg');
        $token = auth('api')->login($admin);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/members/{$member->id}", [
                'nickname' => '编辑后昵称',
                'avatar' => '',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.nickname', '编辑后昵称');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'nickname' => '编辑后昵称',
        ]);
    }

    public function test_member_edit_accepts_png_avatar_from_configured_qiniu_domain(): void
    {
        $admin = $this->createUser('member_png_editor', 'png-member@example.com', '13800000005');
        $member = $this->createMember('13800000006', 'PNG前昵称', '');
        $token = auth('api')->login($admin);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/members/{$member->id}", [
                'nickname' => 'PNG后昵称',
            'avatar' => 'https://cdn.chouy.xyz/upload/member-avatar.png',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.avatar', 'https://cdn.chouy.xyz/upload/member-avatar.png');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'nickname' => 'PNG后昵称',
            'avatar' => 'https://cdn.chouy.xyz/upload/member-avatar.png',
        ]);
    }

    public function test_member_edit_accepts_full_form_payload_with_blank_app_version(): void
    {
        $admin = $this->createUser('member_full_editor', 'full-member@example.com', '13800000009');
        $member = $this->createMember('13800000010', '完整表单前昵称', '');
        $token = auth('api')->login($admin);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/members/{$member->id}", [
                'id' => $member->id,
                'userId' => (string) $member->user_id,
                'memberLevel' => 1,
                'realname' => '测试会员',
                'nickname' => '完整表单后昵称',
                'gender' => 1,
                'avatar' => '',
                'birthday' => 0,
                'provinceCode' => null,
                'cityCode' => null,
                'districtCode' => null,
                'address' => null,
                'intro' => null,
                'signature' => null,
                'admire' => null,
                'device' => 5,
                'deviceCode' => null,
                'pushAlias' => '',
                'source' => 2,
                'status' => 1,
                'appVersion' => '',
                'code' => null,
                'loginIp' => null,
                'loginAt' => 0,
                'loginRegion' => null,
                'loginCount' => 0,
                'city' => ['', '', ''],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.nickname', '完整表单后昵称')
            ->assertJsonPath('data.appVersion', '');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'nickname' => '完整表单后昵称',
            'app_version' => '',
        ]);
    }

    public function test_member_edit_converts_birthday_date_string_to_timestamp(): void
    {
        $admin = $this->createUser('member_birthday_editor', 'birthday-member@example.com', '13800000011');
        $member = $this->createMember('13800000012', '生日前昵称', '');
        $token = auth('api')->login($admin);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/members/{$member->id}", [
                'nickname' => '生日后昵称',
                'birthday' => '1990-09-12',
                'avatar' => '',
                'appVersion' => '',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.nickname', '生日后昵称');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'nickname' => '生日后昵称',
            'birthday' => strtotime('1990-09-12'),
        ]);
    }

    public function test_member_edit_rejects_avatar_from_non_qiniu_domain(): void
    {
        $admin = $this->createUser('member_invalid_editor', 'invalid-member@example.com', '13800000007');
        $member = $this->createMember('13800000008', '非法前昵称', '');
        $token = auth('api')->login($admin);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/members/{$member->id}", [
                'avatar' => 'https://example.com/member-avatar.png',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('data.errors.avatar.0', '头像 不是在本站上传的头像图片地址');
    }

    protected function createUser(string $username, string $email, string $phone): User
    {
        return User::query()->create([
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
    }

    protected function createMember(string $userPhone, string $nickname, string $avatar): Member
    {
        $user = $this->createUser("user_{$userPhone}", "{$userPhone}@example.com", $userPhone);

        return Member::query()->create([
            'user_id' => (string) $user->id,
            'member_level' => 1,
            'realname' => '测试会员',
            'nickname' => $nickname,
            'gender' => 1,
            'avatar' => $avatar,
            'device' => 5,
            'source' => 2,
            'status' => 1,
        ]);
    }
}
