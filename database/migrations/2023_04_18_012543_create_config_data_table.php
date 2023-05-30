<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigDataTable extends Migration
{
    /**
     * 详细配置表
     *
     * @return void
     */
    public function up()
    {
        Schema::create('config_data', function (Blueprint $table) {
            $table->id();
            $table->string('title', 50)->index('index_title')->comment("配置标题");
            $table->string('code', 100)->index('index_code')->comment("配置编码");
            $table->text('value')->comment("配置值");
            $table->string('options')->comment("配置项");
            $table->unsignedInteger('config_id')->default(0)->comment("配置ID");
            $table->string('type', 16)->comment("配置类型");
            $table->unsignedTinyInteger('status')->default(1)->comment("状态：1正常 2停用");
            $table->unsignedSmallInteger('sort')->default(0)->comment("排序");
            $table->string('note', 500)->comment("配置说明");
            $table->unsignedInteger('create_user')->default(0)->comment("添加人");
            $table->unsignedInteger('created_at')->default(0)->comment("添加时间");
            $table->unsignedInteger('update_user')->default(0)->comment("更新人");
            $table->unsignedInteger('updated_at')->default(0)->comment("更新时间");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('config_data');
    }
}
