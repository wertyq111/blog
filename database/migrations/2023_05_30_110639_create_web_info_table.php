<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 网站信息表
     */
    public function up(): void
    {
        Schema::create('web_info', function (Blueprint $table) {
            $table->id();
            $table->string('web_name')->comment('网站名称');
            $table->string('web_title')->comment('网站标题');
            $table->string('notices', 512)->comment('公告');
            $table->string('footer')->comment('页脚');
            $table->string('background_image')->nullable()->comment('背景');
            $table->string('avatar')->comment('头像');
            $table->text('random_avatar')->nullable()->comment('随机头像');
            $table->text('random_name')->nullable()->comment('随机名称');
            $table->text('random_cover')->nullable()->comment('随机封面');
            $table->text('waifu_json')->nullable()->comment('看板娘消息');
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
        Schema::dropIfExists('web_info');
    }
};
