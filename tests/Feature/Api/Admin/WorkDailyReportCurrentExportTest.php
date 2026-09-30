<?php

use App\Models\Admin\WorkDailyReportExport;
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

    Schema::create('work_daily_report_exports', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('user_id');
        $table->string('type', 20);
        $table->string('kind', 20)->default('work');
        $table->date('period_start');
        $table->date('period_end');
        $table->string('model', 120)->nullable();
        $table->string('status', 20);
        $table->string('file_name', 255);
        $table->longText('content')->nullable();
        $table->text('error_message')->nullable();
        $table->unsignedInteger('started_at')->default(0);
        $table->unsignedInteger('finished_at')->default(0);
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });
});

function currentExportMake(int $userId, string $kind, string $status): WorkDailyReportExport
{
    $export = new WorkDailyReportExport();
    $export->fill([
        'user_id' => $userId,
        'type' => 'month',
        'kind' => $kind,
        'period_start' => '2026-09-01',
        'period_end' => '2026-09-30',
        'status' => $status,
        'file_name' => $kind . '.md',
    ]);
    $export->save();

    return $export;
}

function currentExportLogin(): array
{
    $user = User::query()->create([
        'username' => 'current_export_admin',
        'email' => 'current-export-admin@example.com',
        'phone' => '13800000032',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);
    $roleId = DB::table('roles')->insertGetId(['name' => '超级管理员', 'code' => 'super', 'deleted_at' => 0]);
    DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $roleId]);

    return [$user, auth('api')->login($user)];
}

// 两份报表并行生成，成长记录可能先完成且 id 更大；刷新页面时必须拿到还在生成的工作报表，前端才会继续轮询。
it('当前导出优先返回生成中的任务', function () {
    [$user, $token] = currentExportLogin();
    $running = currentExportMake($user->id, WorkDailyReportExport::KIND_WORK, WorkDailyReportExport::STATUS_RUNNING);
    currentExportMake($user->id, WorkDailyReportExport::KIND_GROWTH, WorkDailyReportExport::STATUS_COMPLETED);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/work-daily/report/export/current')
        ->assertOk()
        ->assertJsonPath('data.export.id', $running->id)
        ->assertJsonPath('data.active', true);
});

it('没有生成中的任务时返回最近一条', function () {
    [$user, $token] = currentExportLogin();
    currentExportMake($user->id, WorkDailyReportExport::KIND_WORK, WorkDailyReportExport::STATUS_COMPLETED);
    $latest = currentExportMake($user->id, WorkDailyReportExport::KIND_GROWTH, WorkDailyReportExport::STATUS_COMPLETED);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/work-daily/report/export/current')
        ->assertOk()
        ->assertJsonPath('data.export.id', $latest->id)
        ->assertJsonPath('data.active', false);
});
