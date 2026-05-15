<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_daily_tags', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->string('name', 50)->comment('标签名称');
            $table->unsignedInteger('create_user')->default(0)->comment('创建人，0=系统预设');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间');

            $table->unique(['name', 'create_user'], 'uk_name_user');
        });

        // 插入预设标签
        $now = time();
        $presets = ['需求开发', 'Bug修复', '文档编写', '会议沟通', '代码审查', '技术调研', '部署运维', '学习成长'];
        $rows = array_map(fn($name) => [
            'name'        => $name,
            'create_user' => 0,
            'created_at'  => $now,
            'updated_at'  => $now,
            'deleted_at'  => 0,
        ], $presets);

        DB::table('work_daily_tags')->insert($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_daily_tags');
    }
};
