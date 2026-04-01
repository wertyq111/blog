<?php

namespace Tests\Feature\Api\User;

use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EditUserTest extends TestCase
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
            $table->string('code')->nullable();
            $table->unsignedInteger('deleted_at')->default(0);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedBigInteger('role_id')->default(0);
        });
    }

    public function test_editing_user_with_blank_password_preserves_existing_password(): void
    {
        $admin = User::query()->create([
            'username' => 'admin_user',
            'email' => 'admin@example.com',
            'phone' => '13800000001',
            'password' => bcrypt('admin-password'),
            'status' => 1,
        ]);

        $targetUser = User::query()->create([
            'username' => 'target_user',
            'email' => 'target@example.com',
            'phone' => '13800000002',
            'password' => bcrypt('existing-password'),
            'status' => 1,
        ]);

        $originalPasswordHash = $targetUser->password;
        $token = auth('api')->login($admin);

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/users/{$targetUser->id}", [
                'username' => 'target_user',
                'password' => '',
                'role_ids' => [],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.username', 'target_user');

        $targetUser->refresh();

        $this->assertSame($originalPasswordHash, $targetUser->password);
        $this->assertTrue(Hash::check('existing-password', $targetUser->password));
    }
}
