<?php

namespace App\Services\Api\Tobacco;

use App\Models\Tobacco\TobaccoOrder;

class OrderService extends BaseService
{
    /**
     * 添加
     *
     * @param TobaccoOrder $customer
     * @param array $data
     * @return TobaccoOrder
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/25 16:22
     */
    public function add(array $data, TobaccoOrder $order = null)
    {
        if ($order == null) {
            $order = new TobaccoOrder();
        }
        $order->fill($data);
        $id = $order->insertGetId($data);

        return $id;
    }
}
