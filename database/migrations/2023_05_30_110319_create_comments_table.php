<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 文章评论表
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->integer('source')->index('index_source')->comment('来源标识');
            $table->string('type')->comment('评论来源类型');
            $table->integer('parent_comment_id')->default(0)->index('index_parent')->comment('父评论ID');
            $table->integer('member_id')->index('index_member')->comment('会员ID');
            $table->integer('floor_comment_id')->nullable()->comment('楼层评论ID');
            $table->integer('parent_user_id')->nullable()->comment('父发表用户名ID');
            $table->string('like_count', 256)->nullable()->comment('封面');
            $table->text('content')->nullable()->comment('评论内容');
            $table->text('info')->nullable()->comment('评论额外信息');
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
        Schema::dropIfExists('comments');
    }
};
