<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 家庭信息
     */
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id')->index('index_member')->comment('会员ID');
            $table->string('bg_cover', 256)->nullable()->comment('背景封面');
            $table->string('boy_cover', 256)->nullable()->comment('男生头像');
            $table->string('girl_cover', 256)->nullable()->comment('女生头像');
            $table->string('boy_name')->comment('男生昵称');
            $table->string('girl_name')->comment('女生昵称');
            $table->string('timing')->comment('计时');
            $table->string('countdown_title')->nullable()->comment('倒计时标题');
            $table->string('countdown_time')->nullable()->comment('倒计时时间');
            $table->tinyInteger('status')->default(1)->comment('是否启用[0:否，1:是]');
            $table->text('family_info')->comment('额外信息');
            $table->integer('like_count')->default(0)->comment('点赞数');
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
        Schema::dropIfExists('families');
    }
};
