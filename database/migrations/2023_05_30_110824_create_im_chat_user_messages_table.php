<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 单聊记录
     */
    public function up(): void
    {
        Schema::create('im_chat_user_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('sender_id')->index('index_sender')->comment('发送ID');
            $table->integer('receiver_id')->index('index_receiver')->comment('接收ID');
            $table->string('content', 1024)->comment('内容');
            $table->tinyInteger('message_status')->default(0)->comment('是否已读[0:未读，1:已读]');
            $table->unsignedInteger('created_at')->default(0)->comment("添加时间");
            $table->unsignedInteger('update_user')->default(0)->comment("更新人");
            $table->unsignedInteger('updated_at')->default(0)->comment("更新时间");
            $table->unsignedInteger('deleted_at')->default(0)->comment("删除时间");
            $table->index(['receiver_id', 'message_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('im_chat_user_messages');
    }
};
