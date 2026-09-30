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
        Schema::table('work_daily_report_exports', function (Blueprint $table) {
            $table->string('kind', 20)->default('work')->after('type')->comment('报表种类：work工作报表 growth个人成长记录');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_daily_report_exports', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
