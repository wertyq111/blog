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
        Schema::create('houses', function (Blueprint $table) {
            $table->id()->comment("主键ID");
            $table->integer('member_id')->index('index_member_id')->default(0)->comment('会员ID');
            $table->integer('pid')->index('index_pid')->default(0)->comment('上级ID');
            $table->string('name')->comment('名称');
            $table->string('pic_url')->comment('图片地址');
            $table->string('coordinate')->nullable()->comment('坐标');
            $table->integer('level')->default(1)->comment('1 - 房间， 2 - 具体位置， 3 - 详细坐标');
            $table->string('remark')->nullable()->comment('备注');
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
        Schema::dropIfExists('houses');
    }
};
