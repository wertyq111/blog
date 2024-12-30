<?php

namespace App\Services\Api\Tobacco;

use App\Models\Tobacco\TobaccoSupplement;

class SupplementService extends BaseService
{
    /**
     * 添加
     *
     * @param TobaccoSupplement $supplement
     * @param array $data
     * @return TobaccoSupplement
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/25 16:22
     */
    public function add(array $data, TobaccoSupplement $supplement = null)
    {
        if ($supplement == null) {
            $supplement = new TobaccoSupplement();
        }
        $supplement->fill($data);
        $id = $supplement->insertGetId($data);

        return $id;
    }
}
