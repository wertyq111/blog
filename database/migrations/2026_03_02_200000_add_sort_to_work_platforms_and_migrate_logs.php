<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSortToWorkPlatformsAndMigrateLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('work_platforms', 'sort')) {
            Schema::table('work_platforms', function (Blueprint $table) {
                $table->integer('sort')->default(0)->nullable(false)->after('status');
            });
        }

        // 数据迁移：把以 platform 为维度的多条日志合并为按 date 一条 JSON 记录
        $rows = DB::table('work_daily_logs')->orderBy('user_id')->orderBy('log_date')->get();
        $grouped = [];
        foreach ($rows as $r) {
            $key = $r->user_id . '|' . $r->log_date;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $r;
        }

        foreach ($grouped as $key => $items) {
            // 如果只有一条并且 content 看起来像 JSON，就跳过
            if (count($items) === 1) {
                $single = $items[0];
                $decoded = json_decode($single->content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    continue;
                }
            }

            // 合并
            $platforms = [];
            foreach ($items as $it) {
                $platform = DB::table('work_platforms')->where('id', $it->platform_id)->first();
                $platforms[] = [
                    'platform_id' => $it->platform_id,
                    'platform_name' => $platform ? $platform->name : null,
                    'content' => $it->content,
                ];
            }

            $payload = json_encode(['platforms' => $platforms], JSON_UNESCAPED_UNICODE);

            // 删除旧的这些行，插入一条新的
            $userId = $items[0]->user_id;
            $date = $items[0]->log_date;

            DB::table('work_daily_logs')->where('user_id', $userId)->where('log_date', $date)->delete();
            DB::table('work_daily_logs')->insert([
                'user_id' => $userId,
                'platform_id' => null,
                'log_date' => $date,
                'content' => $payload,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // We won't attempt to reverse the data migration
        if (Schema::hasColumn('work_platforms', 'sort')) {
            Schema::table('work_platforms', function (Blueprint $table) {
                $table->dropColumn('sort');
            });
        }
    }
}
