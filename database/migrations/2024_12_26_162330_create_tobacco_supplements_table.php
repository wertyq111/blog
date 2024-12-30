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
        Schema::create('tobacco_supplements', function (Blueprint $table) {
            $table->id()->comment("主键ID");
            $table->integer('customer')->index('index_customer')->comment('客户');
            $table->integer('number1')->comment('贵烟(硬黄精品)补供数');
            $table->integer('number2')->comment('云烟（云龙）补供数');
            $table->integer('number3')->comment('长白山（777）补供数');
            $table->integer('number4')->comment('双喜（软经典）补供数');
            $table->integer('number5')->comment('黄金叶（乐途）补供数');
            $table->integer('number6')->comment('芙蓉王（硬）补供数');
            $table->integer('number7')->comment('泰山（硬红八喜)补供数');
            $table->integer('number8')->comment('好猫（细支长乐）补供数');
            $table->integer('number9')->comment('好猫（金丝猴）补供数');
            $table->integer('number10')->comment('利群（长嘴）补供数');
            $table->integer('number11')->comment('利群（软红长嘴）补供数');
            $table->string('settle_date', 10)->index('idx_settle_date')->comment('结单日期');
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
        Schema::dropIfExists('tobacco_supplements');
    }
};
