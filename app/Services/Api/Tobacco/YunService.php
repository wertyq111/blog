<?php

namespace App\Services\Api\Tobacco;

use App\Models\Tobacco\TobaccoYun;

class YunService extends BaseService
{
    /**
     * 添加
     *
     * @param TobaccoYun $yun
     * @param array $data
     * @return TobaccoYun
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/25 16:22
     */
    public function add(array $data, TobaccoYun $yun = null)
    {
        if ($yun == null) {
            $yun = new TobaccoYun();
        }
        $yun->fill($data);
        $id = $yun->insertGetId($data);

        return $id;
    }
}
