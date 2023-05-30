<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 聊天群
     */
    public function up(): void
    {
        Schema::create('im_chat_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('群名');
            $table->integer('member_id')->index('index_member')->comment('群主ID');
            $table->string('avatar', 256)->nullable()->comment('群头像');
            $table->string('introduction', 256)->nullable()->comment('简介');
            $table->string('notice', 1024)->nullable()->comment('公告');
            $table->tinyInteger('in_type')->default(1)->comment('进入方式[0:无需验证，1:需要群主或管理员同意]');
            $table->tinyInteger('group_type')->default(1)->comment('类型[1:聊天群，2:话题]');
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
        Schema::dropIfExists('im_chat_groups');
    }
};
