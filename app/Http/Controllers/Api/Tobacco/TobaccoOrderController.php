<?php

namespace App\Http\Controllers\Api\Tobacco;


use App\Http\Controllers\BaseController;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Imports\TobaccoOrderImport;
use App\Models\Tobacco\TobaccoOrder;
use App\Services\Api\Tobacco\OrderService;
use App\Services\Api\Tobacco\SupplyService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TobaccoOrderController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new OrderService();
    }


    /**
     * 列表 - 分页
     *
     * @param FormRequest $request
     * @param TobaccoOrder $model
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 09:52
     */
    public function index(FormRequest $request, TobaccoOrder $model)
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
        $orders = $this->queryBuilder($model, true, $config);

        foreach($orders as &$order) {
            $customer = $this->service->getCustomer($order['customer']);

            $order['customer_name'] = $customer ? $customer['name'] : '';
            unset($order);
        }

        return $this->resource($orders, ['time' => true, 'collection' => true]);
    }

    /**
     * 获取表格列
     *
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 13:45
     */
    public function getColumns()
    {
        $columns = $this->service->getColumns('tobacco-order');

        return response()->json($columns);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/25 14:43
     */
    public function import(FormRequest $request)
    {
        $result = upload_file($request);
        // 文件路径
        $file_path = $result['data']['file_path'];
        // 文件绝对路径
        $file_path = ATTACHMENT_PATH . $file_path;

        $import = new TobaccoOrderImport();
        // 导入Excel
        Excel::import($import, $file_path);
        $importMsg = $import->importMsg;
        $result = [
            'message' => "导入成功 {$importMsg['num']} 条记录",
            'errors' => $importMsg['errors']
        ];
        return response()->json($result);
    }
}
