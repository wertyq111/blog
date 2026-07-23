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
        Schema::create('platform_script_runs', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->string('script_key', 64)->index('index_script_key')->comment('脚本标识');
            $table->string('ordr_no', 40)->index('index_ordr_no')->comment('订单号');
            $table->string('appl_id', 64)->default('')->comment('申请编号');
            $table->string('cntrct_no', 64)->default('')->comment('合同号');
            $table->string('cntpr_nme', 128)->default('')->comment('交易对手户名');
            $table->string('cust_bank_acct_no', 64)->default('')->comment('收款银行账户');
            $table->string('cust_pay_amt', 32)->default('')->comment('提款金额');
            $table->string('actcpe_bchnw_id', 64)->default('')->comment('开户网点号');
            $table->string('actope_bchnw_nme', 255)->default('')->comment('开户网点名');
            $table->text('raw_text')->comment('原始粘贴文本');
            $table->text('request_data')->comment('组装后的请求报文JSON');
            $table->longText('output')->comment('远端执行输出');
            $table->string('status', 16)->comment('执行状态：success/failed');
            $table->text('error')->nullable()->comment('失败信息');
            $table->unsignedInteger('create_user')->default(0)->comment('创建人');
            $table->unsignedInteger('created_at')->default(0)->comment('添加时间');
            $table->unsignedInteger('update_user')->default(0)->comment('更新人');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_script_runs');
    }
};
