<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_docs', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->unsignedBigInteger('category_id')->default(0)->index('index_category_id')->comment('分类ID');
            $table->string('title', 255)->comment('标题');
            $table->longText('content')->comment('文档内容');
            $table->string('template_type', 50)->default('custom')->comment('模板类型');
            $table->text('tags')->nullable()->comment('标签JSON');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1启用 0停用');
            $table->unsignedTinyInteger('priority')->default(0)->comment('优先级');
            $table->string('source', 255)->nullable()->comment('来源/出处');
            $table->unsignedTinyInteger('is_pin')->default(0)->comment('是否置顶 1是 0否');
            $table->unsignedInteger('create_user')->default(0)->comment('添加人');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('update_user')->default(0)->comment('更新人');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间');
        });

        // 全文索引（标题 + 内容）
        DB::statement('ALTER TABLE work_docs ADD FULLTEXT INDEX index_fulltext_title_content (title, content)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_docs');
    }
};
