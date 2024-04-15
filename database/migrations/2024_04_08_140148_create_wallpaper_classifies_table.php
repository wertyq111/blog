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
        Schema::create('wallpaper_classifies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分类名称');
            $table->string('pic_url')->comment('略缩图');
            $table->string('select')->index('index_select')->comment('是否推荐');
            $table->string('sort')->index('index_sort')->comment('排序值');
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
        Schema::dropIfExists('wallpaper_classfies');
    }
};
