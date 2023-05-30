<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticesTable extends Migration
{
    /**
     * 通知表
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150)->index('index_title')->comment("通知标题");
            $table->text('content')->nullable()->comment("通知内容");
            $table->unsignedTinyInteger('source')->default(1)->comment("通知来源：1云平台");
            $table->unsignedTinyInteger('is_top')->default(2)->comment("是否置顶：1已置顶 2未置顶");
            $table->unsignedInteger('browse')->default(0)->comment("阅读量");
            $table->unsignedTinyInteger('status')->default(1)->comment("发布状态：1草稿箱 2立即发布 3定时发布");
            $table->unsignedInteger('create_user')->default(0)->comment("添加人");
            $table->unsignedInteger('created_at')->default(0)->comment("添加时间");
            $table->unsignedInteger('update_user')->default(0)->comment("更新人");
            $table->unsignedInteger('updated_at')->default(0)->comment("更新时间");
            $table->unsignedInteger('deleted_at')->default(0)->comment("删除时间");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notices');
    }
}
