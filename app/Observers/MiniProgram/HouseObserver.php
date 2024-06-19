<?php

namespace App\Observers\MiniProgram;

use App\Models\MiniProgram\House;
use App\Models\MiniProgram\Material;
use Illuminate\Support\Facades\DB;

class HouseObserver
{

    /**
     * 删除对应的物料
     *
     * @param Material $material
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 16:26
     */
    public function deleted(House $house)
    {
        // 查询下级物料
        $materials = DB::table('materials')->where([
            'hid' => $house->id,
            'deleted_at' => 0
        ])->get();

        foreach($materials as $material) {
            $material->delete();
        }
    }
}
