<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pomo_settings', function (Blueprint $table) {
            $table->id()->comment('主键ID');
            $table->unsignedInteger('create_user')->default(0)->unique('uniq_create_user')->comment('所属用户');
            $table->unsignedInteger('focus_min')->default(25)->comment('专注时长(分)');
            $table->unsignedInteger('short_break_min')->default(5)->comment('短休(分)');
            $table->unsignedInteger('long_break_min')->default(15)->comment('长休(分)');
            $table->unsignedInteger('long_break_every')->default(4)->comment('每N个专注后长休');
            $table->unsignedTinyInteger('auto_start_next')->default(0)->comment('自动进入下一阶段 0否 1是');
            $table->unsignedTinyInteger('sound_on')->default(1)->comment('提示音 0关 1开');
            $table->string('white_noise', 50)->nullable()->comment('白噪音类型 rain/forest/wave 或空');
            $table->decimal('white_noise_volume', 3, 2)->default(0.60)->comment('白噪音音量 0~1');
            $table->unsignedInteger('update_user')->default(0)->comment('更新人');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('deleted_at')->default(0)->comment('删除时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pomo_settings');
    }
};
