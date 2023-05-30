<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 聊天群成员
     */
    public function up(): void
    {
        Schema::create('im_chat_group_users', function (Blueprint $table) {
            $table->id();
            $table->integer('group_id')->index('index_group')->comment('群ID');
            $table->integer('member_id')->index('index_member')->comment('会员ID');
            $table->integer('verify_member_id')->index('index_verify_member')->comment('审核会员ID');
            $table->string('remark', 256)->nullable()->comment('备注');
            $table->tinyInteger('admin_flag')->default(0)->comment('是否管理员[0:否，1:是]');
            $table->tinyInteger('status')->default(0)->comment('用户状态[0:未审核，1:审核通过，2:禁言]');
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
        Schema::dropIfExists('im_chat_group_users');
    }
};
