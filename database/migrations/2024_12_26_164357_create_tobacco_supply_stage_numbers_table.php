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
        Schema::create('tobacco_supply_stage_numbers', function (Blueprint $table) {
            $table->id()->comment("主键ID");
            $table->integer('supply')->index('index_supply')->comment('供货限量');
            $table->integer('stage')->index('index_stage')->comment('客户分类(档位)');
            $table->integer('number')->comment('数量');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tobacco_supply_stage_numbers');
    }
};
