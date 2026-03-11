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
        if (!Schema::hasColumn('work_doc_categories', 'icon')) {
            Schema::table('work_doc_categories', function (Blueprint $table) {
                $table->string('icon', 64)->nullable()->after('name')->comment('分类图标');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('work_doc_categories', 'icon')) {
            Schema::table('work_doc_categories', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }
    }
};
