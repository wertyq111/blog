<?php

namespace App\Services\Api\Tobacco;

use App\Models\Tobacco\TobaccoDesignated;

class DesignatedService extends BaseService
{
    /**
     * 添加
     *
     * @param TobaccoDesignated $designated
     * @param array $data
     * @return TobaccoDesignated
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/25 16:22
     */
    public function add(array $data, TobaccoDesignated $designated = null)
    {
        if ($designated == null) {
            $designated = new TobaccoDesignated();
        }
        $designated->fill($data);
        $id = $designated->insertGetId($data);

        return $id;
    }
}
