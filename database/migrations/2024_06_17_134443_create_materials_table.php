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
        Schema::create('materials', function (Blueprint $table) {
            $table->id()->comment("主键ID");
            $table->integer('member_id')->index('index_member_id')->default(0)->comment('会员ID');
            $table->integer('house_id')->index('index_house_id')->default(0)->comment('房屋ID');
            $table->integer('pid')->index('index_pid')->default(0)->comment('上级ID');
            $table->string('name')->index('index_num')->comment('名称');
            $table->integer('num')->default(0)->comment('数量');
            $table->string('style')->nullable()->comment('风格');
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
        Schema::dropIfExists('materials');
    }
};
