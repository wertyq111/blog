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
        Schema::create('work_platforms', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->string('name', 50)->index('index_name')->comment('平台名称');
            $table->boolean('status')->default(1)->comment('状态：1启用 0禁用');
            $table->smallInteger('sort')->default(0)->comment('排序');
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
        Schema::dropIfExists('work_platforms');
    }
};
