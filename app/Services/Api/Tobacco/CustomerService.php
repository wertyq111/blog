<?php

namespace App\Services\Api\Tobacco;

use App\Models\Tobacco\TobaccoCustomer;
use App\Models\Tobacco\TobaccoDesignated;
use App\Models\Tobacco\TobaccoOrder;
use App\Models\Tobacco\TobaccoSupplement;
use App\Models\Tobacco\TobaccoYun;

class CustomerService extends BaseService
{
    /**
     * 添加
     *
     * @param TobaccoCustomer $customer
     * @param array $data
     * @return TobaccoCustomer
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/25 16:22
     */
    public function add(array $data, TobaccoCustomer $customer = null)
    {
        if ($customer == null) {
            $customer = new TobaccoCustomer();
        }
        $customer->fill($data);
        $id = $customer->insertGetId($data);

        return $id;
    }

    /**
     * 补供
     *
     * @param $map
     * @return null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/30 09:13
     */
    public function getSupplements($map)
    {
        $model = new TobaccoSupplement();

        $supplements = $model->where($map)->get();

        return $supplements ?? null;
    }

    /**
     * 云龙补供
     *
     * @param $map
     * @return null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/30 09:16
     */
    public function getYuns($map)
    {
        $model = new TobaccoYun();

        $yuns = $model->where($map)->get();

        return $yuns ? $yuns->toArray() : null;
    }

    /**
     * 1024定点供货
     *
     * @param $map
     * @return null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/30 09:17
     */
    public function getDesignateds($map)
    {
        $model = new TobaccoDesignated();

        $designateds = $model->where($map)->get();

        return $designateds ? $designateds->toArray() : null;
    }

    /**
     * 本次订货
     *
     * @param $map
     * @return null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/30 09:18
     */
    public function getOrders($map)
    {
        $model = new TobaccoOrder();

        $orders = $model->where($map)->get();

        return $orders ? $orders->toArray() : null;
    }
}
