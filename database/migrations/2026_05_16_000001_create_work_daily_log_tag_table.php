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
        Schema::create('work_daily_log_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('work_daily_log_id')->comment('日志ID');
            $table->unsignedBigInteger('work_daily_tag_id')->comment('标签ID');

            $table->primary(['work_daily_log_id', 'work_daily_tag_id'], 'pk_log_tag');
            $table->index('work_daily_log_id', 'idx_log_id');
            $table->index('work_daily_tag_id', 'idx_tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_daily_log_tag');
    }
};
