<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pomo_tasks', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->string('title', 255)->comment('任务标题');
            $table->unsignedInteger('estimated_pomos')->default(1)->comment('预估番茄数');
            $table->unsignedInteger('completed_pomos')->default(0)->comment('已完成番茄数');
            $table->unsignedTinyInteger('done')->default(0)->comment('是否完成 0否 1是');
            $table->unsignedInteger('sort')->default(0)->index('index_sort')->comment('排序');
            $table->unsignedInteger('create_user')->default(0)->index('index_create_user')->comment('所属用户');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('update_user')->default(0)->comment('更新人');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pomo_tasks');
    }
};
