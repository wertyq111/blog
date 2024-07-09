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
        Schema::create('baby_care', function (Blueprint $table) {
            $table->id()->comment("主键ID");
            $table->integer('member_id')->index('index_member_id')->default(0)->comment('会员ID');
            $table->string('title')->comment('标题');
            $table->text('content')->nullable()->comment('内容');
            $table->string('video_url')->comment('图片地址');
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
        Schema::dropIfExists('baby_care');
    }
};
