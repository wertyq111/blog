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
        Schema::table('work_platforms', function (Blueprint $table) {
            $table->string('category', 20)->default('work')->after('name')->comment('大类：work工作 study学习 personal个人');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_platforms', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
