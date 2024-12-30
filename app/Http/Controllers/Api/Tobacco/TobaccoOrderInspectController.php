<?php

namespace App\Http\Controllers\Api\Tobacco;


use App\Http\Controllers\BaseController;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Models\Tobacco\TobaccoCustomer;
use App\Services\Api\Tobacco\CustomerService;

class TobaccoOrderInspectController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new CustomerService();
    }


    /**
     * 列表 - 分页
     *
     * @param FormRequest $request
     * @param TobaccoCustomer $model
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 09:52
     */
    public function index(FormRequest $request, TobaccoCustomer $model)
    {
        $data = $request->all();

        $perPage = $data['limit'] ?? ($data['perPage'] ?? null);

        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($model->getRequestFilters());

        // 查询条件
        $conditions = [];

        // 编号查询
        if(isset($data['code'])) {
            $conditions[] = ['code', 'like', '%'. $data['code']. '%'];
        }

        // 名称查询
        if(isset($data['name'])) {
            $conditions[] = ['name', 'like', '%'. $data['name']. '%'];
        }

        $config = [
            'allowedFilters' => $allowedFilters,
            'perPage' => $perPage,
            'conditions' => $conditions,
            'orderBy' => [['id' => 'asc']]
        ];
        $customers = $this->queryBuilder($model, true, $config);

        $map = [];
        // 结单日期查询
        $settleDate = getter($data, 'settle_date', null);
        if ($settleDate && is_array($settleDate)) {
            $map[] = ['settle_date', '>=', $settleDate[0]];
            $map[] = ['settle_date', '<=', $settleDate[1]];
        }

        foreach($customers as &$customer) {
            $customerMap = $map;

            // 客户分类信息
            $stage = $this->service->getStage($customer['stage']);
            $customer['stageName'] = $stage['name'];

            // 本轮供货档位数量
            $supplyStatistics = $this->service->getSupplyStatistics($data);
            $customer['supplyNumber'] = $supplyStatistics['stage'. $customer['stage']];

            // 客户
            $customerMap[] = ['customer', $customer['id']];

            // 补供合计
            $supplements = $this->service->getSupplements($customerMap);
            $customer['supplement'] = 0;
            if($supplements) {
                foreach($supplements as $supplement) {
                    $customer['supplement'] = $supplement->getTotalNumber();
                }
            }

            // 云龙补供合计
            $yuns = $this->service->getYuns($customerMap);
            $customer['yun'] = 0;
            if($yuns) {
                foreach($yuns as $yun) {
                    $customer['yun'] = $yun['number'];
                }
            }

            // 1024合计
            $designateds = $this->service->getDesignateds($customerMap);
            $customer['designated'] = 0;
            if($designateds) {
                foreach($designateds as $designated) {
                    $customer['designated'] = $designated['number'];
                }
            }

            // 本轮订货数量
            $orders = $this->service->getOrders($customerMap);
            $customer['order'] = 0;
            if($orders) {
                foreach($orders as $order) {
                    $customer['order'] = $order['order_number'];
                }
            }

            // 合计供货数量
            $customer['total'] = bcadd(
                bcadd($customer['supplyNumber'], $customer['supplement']),
                bcadd($customer['yun'], $customer['designated'])
            );

            // 订单量差值
            $customer['difference'] = bcsub($customer['total'], $customer['order']);

            unset($customer);
        }

        return $this->resource($customers, ['time' => true, 'collection' => true]);
    }
}
