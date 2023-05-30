<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 资源信息
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id')->index('index_member')->comment('会员ID');
            $table->string('type')->comment('资源类型');
            $table->string('path', 256)->comment('资源路径');
            $table->integer('size')->comment('资源内容的大小，单位：字节');
            $table->string('mime_type')->nullable()->comment('资源的 MIME 类型');
            $table->tinyInteger('status')->default(1)->comment('是否启用[0:否，1:是]');
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
        Schema::dropIfExists('resources');
    }
};
