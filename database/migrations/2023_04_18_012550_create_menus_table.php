<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenusTable extends Migration
{
    /**
     * 菜单表
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->integer('pid')->default(0)->index('index_pid')->comment("父级ID");
            $table->string('title', 30)->index('index_name')->comment("菜单标题");
            $table->string('icon', 50)->nullable()->comment("图标");
            $table->string('path', 150)->nullable()->comment("菜单路径");
            $table->string('component', 150)->nullable()->comment("菜单组件");
            $table->string('target', 30)->nullable()->comment("打开方式：0组件 1内链 2外链");
            $table->string('permission', 150)->nullable()->comment("权限标识");
            $table->boolean('type')->default(0)->comment("类型：0菜单 1节点");
            $table->boolean('status')->default(1)->comment("状态：1正常 2禁用");
            $table->unsignedTinyInteger('hide')->default(0)->comment("是否可见：0显示 1隐藏");
            $table->string('note')->nullable()->comment("备注");
            $table->smallInteger('sort')->default(125)->comment("显示顺序");
            $table->integer('create_user')->default(0)->comment("添加人");
            $table->unsignedInteger('created_at')->default(0)->comment("创建时间");
            $table->integer('update_user')->default(0)->comment("更新人");
            $table->unsignedInteger('updated_at')->default(0)->comment("更新时间");
            $table->unsignedInteger('deleted_at')->default(0)->comment("删除时间");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
