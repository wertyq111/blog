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
        Schema::create('todo_items', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->string('title', 255)->comment('标题');
            $table->text('content')->nullable()->comment('描述内容');
            $table->unsignedTinyInteger('status')->default(0)->index('index_status')->comment('状态 0待办 1进行中 2已完成 3已取消');
            $table->unsignedTinyInteger('priority')->default(1)->index('index_priority')->comment('优先级 0低 1中 2高 3紧急');
            $table->date('due_date')->nullable()->index('index_due_date')->comment('截止日期');
            $table->text('tags')->nullable()->comment('标签JSON');
            $table->unsignedBigInteger('platform_id')->default(0)->index('index_platform_id')->comment('关联工作平台');
            $table->unsignedInteger('create_user')->default(0)->comment('添加人');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('update_user')->default(0)->comment('更新人');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间');
        });

        DB::statement('ALTER TABLE todo_items ADD FULLTEXT INDEX index_fulltext_title_content (title, content)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_items');
    }
};
