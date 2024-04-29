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
        Schema::create('init_models', function (Blueprint $table) {
            $table->id()->comment("主键ID");
            $table->string('code')->index('index_code')->comment('框架编码');
            $table->string('name')->index('index_name')->comment('框架名');
            $table->text('template')->comment('模板内容');
            $table->text('tip')->comment('参考提示');
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
        Schema::dropIfExists('init_models');
    }
};
