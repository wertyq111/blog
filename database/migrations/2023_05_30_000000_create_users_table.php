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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable()->comment('用户名');
            $table->string('email')->nullable()->unique()->comment('邮箱');
            $table->string('phone')->nullable()->unique()->comment('手机号');
            $table->string('openid')->unique()->nullable()->comment('第三方绑定openId');
            $table->string('unionid')->unique()->nullable()->comment('第三方绑定unioId');
            $table->string('password')->nullable()->comment('密码');
            $table->timestamp('email_verified_at')->nullable()->comment('邮箱验证时间');
            $table->integer('status')->default(0)->comment('账号状态(0 - 未激活, 1 - 激活)');

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
