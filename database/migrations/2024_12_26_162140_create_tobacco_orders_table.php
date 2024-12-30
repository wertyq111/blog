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
        Schema::create('tobacco_orders', function (Blueprint $table) {
            $table->id()->comment("主键ID");
            $table->integer('customer')->index('index_customer')->comment('客户');
            $table->integer('require_number')->comment('要货数量');
            $table->integer('order_number')->comment('订单数量');
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
        Schema::dropIfExists('tobacco_orders');
    }
};
