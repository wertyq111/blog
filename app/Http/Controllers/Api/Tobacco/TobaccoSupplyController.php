<?php

namespace App\Http\Controllers\Api\Tobacco;


use App\Http\Controllers\BaseController;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Imports\TobaccoSupplyImport;
use App\Models\Tobacco\TobaccoSupply;
use App\Services\Api\Tobacco\SupplyService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TobaccoSupplyController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new SupplyService();
    }


    /**
     * 列表 - 分页
     *
     * @param FormRequest $request
     * @param TobaccoSupply $supplyModel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 09:52
     */
    public function index(FormRequest $request, TobaccoSupply $supplyModel)
    {
        $data = $request->all();

        $perPage = $data['limit'] ?? ($data['perPage'] ?? null);

        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($supplyModel->getRequestFilters());

        // 查询条件
        $conditions = [];
        if(isset($data['settle_date']) && is_array($data['settle_date'])) {
            $conditions[] = ['settle_date', '>=', $data['settle_date'][0]];
            $conditions[] = ['settle_date', '<=', $data['settle_date'][1]];
        }

        $config = [
            'allowedFilters' => $allowedFilters,
            'perPage' => $perPage,
            'conditions' => $conditions,
            'orderBy' => [['id' => 'asc']]
        ];
        $supplies = $this->queryBuilder($supplyModel, true, $config);
        $emptyStages = $this->service->getStages();
        foreach($supplies as &$supply) {
            foreach(array_keys($emptyStages) as $emptyStage ) {
                $supply[$emptyStage] = 0;
            }

            if($supply->stages->count() > 0) {
                foreach($supply->stages as $stage) {
                    $supply['stage'. $stage['id']] = $stage['pivot']['number'];
                }
            }
        }

        return $this->resource($supplies, ['time' => true, 'collection' => true]);
    }

    /**
     * 统计
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 14:06
     */
    public function statistics(FormRequest $request)
    {
        $result = $this->service->getStatistics($request);

        return response()->json($result);
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
        // 档位列表

        $customList = $this->service->getStages();

        $columns = $this->service->getColumns('tobacco-supply', $customList);

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

        $import = new TobaccoSupplyImport();
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
