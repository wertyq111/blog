<?php

namespace Tests\Feature\Api\User;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateUserInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_user_profile_can_be_updated_and_member_is_created_when_missing(): void
    {
        $user = User::query()->create([
            'username' => 'profile_user',
            'email' => 'before@example.com',
            'phone' => '13800000000',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $response = $this
            ->actingAs($user, 'api')
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
            ->assertStatus(201)
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
}
