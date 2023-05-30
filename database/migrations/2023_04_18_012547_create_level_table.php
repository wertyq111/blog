<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLevelTable extends Migration
{
    /**
     * 职级表
     *
     * @return void
     */
    public function up()
    {
        Schema::create('level', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->index('name')->comment("职级名称");
            $table->unsignedTinyInteger('status')->default(1)->comment("状态：1正常 2停用");
            $table->unsignedSmallInteger('sort')->default(125)->comment("显示顺序");
            $table->unsignedInteger('create_user')->default(0)->comment("添加人");
            $table->integer('created_at')->nullable()->comment("创建时间");
            $table->unsignedInteger('update_user')->default(0)->comment("更新人");
            $table->integer('updated_at')->nullable()->comment("更新时间");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('level');
    }
}
