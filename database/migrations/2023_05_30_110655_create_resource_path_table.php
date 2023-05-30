<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 资源路径
     */
    public function up(): void
    {
        Schema::create('resource_path', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('标题');
            $table->string('classify')->nullable()->comment('分类');
            $table->string('cover', 256)->nullable()->comment('封面');
            $table->string('url', 256)->nullable()->comment('链接');
            $table->text('introduction')->nullable()->comment('简介');
            $table->string('type')->comment('资源类型');
            $table->tinyInteger('status')->default(1)->comment('是否启用[0:否，1:是]');
            $table->string('remark')->nullable()->comment('备注');
            $table->unsignedInteger('created_at')->default(0)->comment("添加时间");
            $table->unsignedInteger('update_user')->default(0)->comment("更新人");
            $table->unsignedInteger('updated_at')->default(0)->comment("更新时间");
            $table->unsignedInteger('deleted_at')->default(0)->comment("删除时间");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_path');
    }
};
