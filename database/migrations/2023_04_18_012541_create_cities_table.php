<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCitiesTable extends Migration
{
    /**
     * 城市表
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pid')->default(0)->comment("父级编号");
            $table->unsignedTinyInteger('level')->default(0)->comment("城市级别：1省 2市 3区");
            $table->string('name', 50)->index('index_name')->comment("城市名称");
            $table->string('citycode', 10)->comment("城市编号（区号）");
            $table->string('p_adcode', 10)->nullable()->comment("父级地理编号");
            $table->string('adcode', 10)->nullable()->comment("地理编号");
            $table->unsignedInteger('lng')->nullable()->comment("城市坐标中心点经度（* 1e6）：如果是中国，此值是 1e7");
            $table->unsignedInteger('lat')->nullable()->comment("城市坐标中心点纬度（* 1e6）");
            $table->unsignedTinyInteger('sort')->default(125)->comment("排序号");
            $table->unsignedInteger('create_user')->default(0)->comment("添加人");
            $table->unsignedInteger('created_at')->default(0)->comment("添加时间");
            $table->unsignedInteger('update_user')->default(0)->comment("更新人");
            $table->unsignedInteger('updated_at')->default(0)->comment("更新时间");
            $table->unsignedInteger('deleted_at')->default(0)->comment("删除时间");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cities');
    }
}
