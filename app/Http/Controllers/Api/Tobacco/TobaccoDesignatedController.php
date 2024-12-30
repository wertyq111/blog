<?php

namespace App\Http\Controllers\Api\Tobacco;


use App\Http\Controllers\BaseController;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Imports\TobaccoDesignatedImport;
use App\Models\Tobacco\TobaccoDesignated;
use App\Services\Api\Tobacco\DesignatedService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TobaccoDesignatedController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new DesignatedService();
    }


    /**
     * 列表 - 分页
     *
     * @param FormRequest $request
     * @param TobaccoDesignated $model
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 09:52
     */
    public function index(FormRequest $request, TobaccoDesignated $model)
    {
        $data = $request->all();

        $perPage = $data['limit'] ?? ($data['perPage'] ?? null);

        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($model->getRequestFilters());

        // 查询条件
        $conditions = [];

        $config = [
            'allowedFilters' => $allowedFilters,
            'perPage' => $perPage,
            'conditions' => $conditions,
            'orderBy' => [['id' => 'asc']]
        ];
        $designateds = $this->queryBuilder($model, true, $config);

        foreach($designateds as &$designated) {
            $customer = $this->service->getCustomer($designated['customer']);

            $designated['customer_name'] = $customer ? $customer['name'] : '找不到指定客户';
            unset($designated);
        }

        return $this->resource($designateds, ['time' => true, 'collection' => true]);
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
        $columns = $this->service->getColumns('tobacco-designated');

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

        $import = new TobaccoDesignatedImport();
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
