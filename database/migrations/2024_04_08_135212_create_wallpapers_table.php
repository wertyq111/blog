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
        Schema::create('wallpapers', function (Blueprint $table) {
            $table->id();
            $table->integer('class_id')->index('index_class_id')->comment('壁纸所属分类ID');
            $table->string('url', 300)->comment('壁纸地址');
            $table->string('description', 500)->comment('壁纸描述');
            $table->string('small_pic_url')->comment('略缩图');
            $table->tinyInteger('score')->comment('评分');
            $table->string('nickname', 200)->comment('发布者昵称');
            $table->json('tags')->comment('标签组');
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
        Schema::dropIfExists('wallpapers');
    }
};
