<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pomo_sessions', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->unsignedBigInteger('task_id')->default(0)->index('index_task_id')->comment('关联任务 0为无');
            $table->char('day', 10)->index('index_day')->comment('完成日期 YYYY-MM-DD 本地时区');
            $table->unsignedInteger('completed_at')->default(0)->comment('完成时间戳');
            $table->unsignedInteger('create_user')->default(0)->index('index_create_user')->comment('所属用户');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('update_user')->default(0)->comment('更新人');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pomo_sessions');
    }
};
