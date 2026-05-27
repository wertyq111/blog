<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_daily_report_exports', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->unsignedInteger('user_id')->index('index_user_id')->comment('导出用户');
            $table->string('type', 20)->index('index_type')->comment('报表类型 month/week/year');
            $table->date('period_start')->index('index_period_start')->comment('开始日期');
            $table->date('period_end')->index('index_period_end')->comment('结束日期');
            $table->string('model', 120)->nullable()->comment('模型');
            $table->string('status', 20)->index('index_status')->comment('状态 pending/running/completed/failed');
            $table->string('file_name', 255)->comment('文件名');
            $table->longText('content')->nullable()->comment('Markdown 内容');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->unsignedInteger('started_at')->default(0)->comment('开始时间');
            $table->unsignedInteger('finished_at')->default(0)->comment('结束时间');
            $table->unsignedInteger('create_user')->default(0)->comment('添加人');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('update_user')->default(0)->comment('更新人');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间');

            $table->index(['user_id', 'status'], 'index_user_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_daily_report_exports');
    }
};
