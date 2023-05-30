<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 50)->nullable()->comment("用户 ID");
            $table->smallInteger('member_level')->default(0)->comment("会员等级");
            $table->string('realname', 50)->nullable()->comment("真实姓名");
            $table->string('nickname', 50)->nullable()->comment("用户昵称");
            $table->tinyInteger('gender')->default(3)->comment("性别（1男 2女 3未知）");
            $table->string('avatar', 180)->default('')->comment("用户头像");
            $table->unsignedInteger('birthday')->default(0)->comment("出生日期");
            $table->string('province_code', 30)->nullable()->comment("户籍省份编号");
            $table->string('city_code', 30)->nullable()->comment("户籍城市编号");
            $table->string('district_code', 30)->nullable()->comment("户籍区/县编号");
            $table->string('address')->nullable()->comment("详细地址");
            $table->text('intro')->nullable()->comment("个人简介");
            $table->string('signature', 30)->nullable()->comment("个性签名");
            $table->string('admire')->nullable()->comment('赞赏');
            $table->boolean('device')->default(0)->comment("设备类型：1苹果 2安卓 3WAP站 4PC站 5后台添加");
            $table->string('device_code', 40)->nullable()->comment("推送的别名");
            $table->string('push_alias', 40)->default('')->comment("推送的别名");
            $table->boolean('source')->default(1)->comment("来源：1、APP注册；2、后台添加；");
            $table->boolean('status')->default(1)->comment("是否启用 1、启用  2、停用");
            $table->string('app_version', 30)->default('')->comment("客户端版本号");
            $table->string('code', 10)->nullable()->comment("我的推广码");
            $table->string('login_ip', 30)->nullable()->comment("最近登录IP");
            $table->unsignedInteger('login_at')->default(0)->comment("登录时间");
            $table->string('login_region', 20)->nullable()->comment("上次登录地点");
            $table->unsignedInteger('login_count')->default(0)->comment("登录总次数");
            $table->integer('create_user')->default(0)->comment("添加人");
            $table->unsignedInteger('created_at')->default(0)->comment("创建时间");
            $table->integer('update_user')->default(0)->comment("修改人");
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
        Schema::dropIfExists('members');
    }
}
