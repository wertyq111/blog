<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 好友
     */
    public function up(): void
    {
        Schema::dropIfExists('im_chat_user_friends');
        Schema::create('im_chat_user_friends', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id')->index('index_member')->comment('会员ID');
            $table->integer('friend_id')->index('index_friend')->comment('好友ID');
            $table->tinyInteger('friend_status')->default(0)->comment('朋友状态[0:未审核，1:审核通过]');
            $table->string('remark')->comment('备注');
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
        Schema::dropIfExists('im_chat_user_friends');
    }
};
