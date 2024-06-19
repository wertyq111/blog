<?php

namespace App\Observers\MiniProgram;

use App\Models\MiniProgram\Material;
use Illuminate\Support\Facades\DB;

class MaterialObserver
{
    /**
     * 保存结束后进行父级数量计算
     *
     * @param Material $material
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 16:22
     */
    public function saved(Material $material)
    {
        // 查询父级物料
        $parent = DB::table('materials')->where([
            'pid' => ['pid', '>', 0],
            'deleted_at' => 0
        ])->first();

        // 计算父级类总数
        if($parent) {
            $parent->num = bcadd($parent->num, $material->num);
            $parent->save();
        }
    }

    /**
     * 删除对应的下级物料
     *
     * @param Material $material
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 16:26
     */
    public function deleted(Material $material)
    {
        // 查询下级物料
        $materials = DB::table('materials')->where([
            'pid' => $material->id,
            'deleted_at' => 0
        ])->get();

        foreach($materials as $material) {
            $material->delete();
        }
    }
}
