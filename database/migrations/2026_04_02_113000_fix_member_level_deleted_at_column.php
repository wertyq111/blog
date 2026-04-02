<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 把会员等级表的软删除字段升级为可承载 Unix 时间戳的整型。
     *
     * 历史表结构把 `deleted_at` 定成了 `unsignedTinyInteger`，在当前软删除实现写入秒级时间戳时
     * 会导致 MySQL 发生数值溢出，从而让批量删除直接返回 500。
     */
    public function up(): void
    {
        if (! Schema::hasTable('member_level')) {
            return;
        }

        Schema::table('member_level', function (Blueprint $table) {
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间')->change();
        });
    }

    /**
     * 回滚时恢复到历史字段类型。
     */
    public function down(): void
    {
        if (! Schema::hasTable('member_level')) {
            return;
        }

        Schema::table('member_level', function (Blueprint $table) {
            $table->unsignedTinyInteger('deleted_at')->default(0)->comment('删除时间')->change();
        });
    }
};
