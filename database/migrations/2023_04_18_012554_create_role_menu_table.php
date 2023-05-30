<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleMenuTable extends Migration
{
    /**
     * 角色与擦弹关联表
     *
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_menu', function (Blueprint $table) {
            $table->unsignedSmallInteger('role_id')->default(0)->comment("角色ID");
            $table->unsignedSmallInteger('menu_id')->default(0)->index('role_id')->comment("菜单ID");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_menu');
    }
}
