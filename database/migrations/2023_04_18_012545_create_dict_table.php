<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDictTable extends Migration
{
    /**
     * 字典表
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dict', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->index('name')->comment("字典名称");
            $table->string('code', 50)->comment("字典值");
            $table->unsignedSmallInteger('sort')->default(125)->comment("显示顺序");
            $table->string('note')->nullable()->comment("字典备注");
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
        Schema::dropIfExists('dict');
    }
}
