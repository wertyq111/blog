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
        Schema::create('article_member_goods', function (Blueprint $table) {
                $table->integer('member_id')->index('index_member_id')->comment('会员ID');
                $table->integer('article_id')->index('index_article_id')->comment('文章ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_member_goods');
    }
};
