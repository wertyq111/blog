<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberLevelTable extends Migration
{
    /**
     * 会员等级表
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_level', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->index('name')->comment("级别名称");
            $table->unsignedSmallInteger('sort')->default(125)->comment("显示顺序");
            $table->unsignedInteger('create_user')->default(0)->comment("添加人");
            $table->unsignedInteger('created_at')->default(0)->comment("创建时间");
            $table->unsignedInteger('update_user')->default(0)->comment("更新人");
            $table->unsignedInteger('updated_at')->default(0)->comment("更新时间");
            $table->unsignedTinyInteger('deleted_at')->default(0)->comment("删除时间");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_level');
    }
}
