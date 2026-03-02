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
        Schema::create('work_daily_logs', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->unsignedBigInteger('platform_id')->index('index_platform_id')->comment('平台ID');
            $table->date('log_date')->index('index_log_date')->comment('记录日期');
            $table->text('content')->comment('工作内容');
            $table->unsignedInteger('create_user')->default(0)->comment('添加人');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('update_user')->default(0)->comment('更新人');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_daily_logs');
    }
};
