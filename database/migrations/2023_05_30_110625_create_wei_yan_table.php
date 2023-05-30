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
        Schema::create('wei_yan', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id')->index('index_member')->comment('会员ID');
            $table->integer('source')->index('index_source')->comment('来源标识');
            $table->text('content')->nullable()->comment('内容');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->string('type')->comment('类型');
            $table->tinyInteger('is_public')->default(0)->comment('是否公开[0:仅自己可见，1:所有人可见]');
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
        Schema::dropIfExists('wei_yan');
    }
};
