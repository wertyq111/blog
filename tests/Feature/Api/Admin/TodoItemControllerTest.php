<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Admin\TodoItem;
use App\Models\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TodoItemControllerTest extends TestCase
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

        Schema::create('todo_items', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->unsignedTinyInteger('priority')->default(1)->index();
            $table->date('due_date')->nullable()->index();
            $table->text('tags')->nullable();
            $table->unsignedBigInteger('platform_id')->default(0)->index();
            $table->unsignedInteger('create_user')->default(0);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('update_user')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->default(0);
        });
    }

    public function test_statistics_counts_canceled_todo_items(): void
    {
        $admin = $this->createSuperAdmin();

        $this->createTodo(['status' => 0]);
        $this->createTodo(['status' => 1]);
        $this->createTodo(['status' => 2]);
        $this->createTodo(['status' => 3]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . auth('api')->login($admin))
            ->getJson('/api/todo/statistics');

        $response
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.pending', 1)
            ->assertJsonPath('data.inProgress', 1)
            ->assertJsonPath('data.completed', 1)
            ->assertJsonPath('data.canceled', 1);
    }

    public function test_edit_rejects_blank_title(): void
    {
        $admin = $this->createSuperAdmin();
        $todo = $this->createTodo(['title' => '原始标题', 'create_user' => $admin->id]);

        $this->withoutExceptionHandling();

        try {
            $this
                ->withHeader('Authorization', 'Bearer ' . auth('api')->login($admin))
                ->postJson("/api/todo/{$todo->id}", [
                    'title' => '   ',
                ]);

            $this->fail('编辑待办时应拒绝空标题');
        } catch (\Exception $exception) {
            $this->assertSame('标题不能为空', $exception->getMessage());
        }

        $this->assertDatabaseHas('todo_items', [
            'id' => $todo->id,
            'title' => '原始标题',
        ]);
    }

    private function createSuperAdmin(): User
    {
        $admin = User::query()->create([
            'username' => 'todo_admin',
            'email' => 'todo-admin@example.com',
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

    private function createTodo(array $attributes = []): TodoItem
    {
        return TodoItem::query()->create(array_merge([
            'title' => '测试待办',
            'content' => '测试内容',
            'status' => 0,
            'priority' => 1,
            'platform_id' => 0,
            'create_user' => 0,
            'update_user' => 0,
            'created_at' => time(),
            'updated_at' => time(),
            'deleted_at' => 0,
        ], $attributes));
    }
}
