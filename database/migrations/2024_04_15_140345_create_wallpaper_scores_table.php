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
        Schema::create('wallpaper_scores', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id')->index('index_member_id')->comment('会员ID');
            $table->integer('wallpaper_id')->index('index_wallpaper_id')->comment('壁纸ID');
            $table->integer('score')->comment('评分');
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
        Schema::dropIfExists('wallpaper_scores');
    }
};
