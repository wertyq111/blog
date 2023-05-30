<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDictDataTable extends Migration
{
    /**
     * 字典信息表
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dict_data', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->index('title')->comment("字典项名称");
            $table->string('code', 50)->comment("字典项值");
            $table->unsignedInteger('dict_id')->default(0)->comment("字典ID");
            $table->unsignedTinyInteger('status')->default(1)->comment("状态：1在用 2停用");
            $table->string('note', 300)->nullable()->comment("备注");
            $table->unsignedSmallInteger('sort')->default(125)->comment("显示顺序");
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
        Schema::dropIfExists('dict_data');
    }
}
