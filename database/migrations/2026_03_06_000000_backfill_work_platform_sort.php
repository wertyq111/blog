<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $platforms = DB::table('work_platforms')->orderBy('created_at', 'asc')->get();
        $i = 1;
        foreach ($platforms as $p) {
            DB::table('work_platforms')->where('id', $p->id)->update(['sort' => $i * 10]);
            $i++;
        }
    }

    public function down()
    {
        // 回退不清除 sort 值，谨慎操作。如需回退可以手动恢复备份。
    }
};
