<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 文章表
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id')->index('index_member')->comment('会员ID');
            $table->integer('category_id')->index('index_category')->comment('分类ID');
            $table->integer('label_id')->index('index_label')->comment('标签ID');
            $table->string('cover', 256)->nullable()->comment('封面');
            $table->string('title')->comment('标题');
            $table->text('content')->nullable()->comment('内容');
            $table->integer('view_count')->default(0)->comment('浏览量');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->integer('view_status')->default(1)->comment('是否可见[0:否，1:是]');
            $table->string('password')->nullable()->comment('密码');
            $table->tinyInteger('recommend_status')->default(0)->comment('是否推荐[0:否，1:是]');
            $table->tinyInteger('comment_status')->default(1)->comment('是否启用评论[0:否，1:是]');
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
        Schema::dropIfExists('articles');
    }
};
