<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\MenuRequest;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Models\Admin\InitModel;
use App\Services\Api\Admin\InitModelService;
use GuzzleHttp\Utils;

class InitModelController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new InitModelService();
    }


    /**
     * 服务器路径列表 - 分页
     *
     * @param FormRequest $request
     * @param InitModel $initModel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 09:52
     */
    public function index(FormRequest $request, InitModel $initModel)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($initModel->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters
        ];
        $initModels = $this->queryBuilder($initModel, true, $config);

        return $this->resource($initModels, ['time' => true, 'collection' => true]);
    }

    /**
     * 服务器路径详情
     *
     * @param InitModel $initModel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 13:28
     */
    public function info(InitModel $initModel)
    {
        return $this->resource($initModel);
    }

    /**
     * 添加服务器路径
     *
     * @param MenuRequest $request
     * @param InitModel $initModel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:07
     */
    public function add(FormRequest $request, InitModel $initModel)
    {
        $data = $request->getSnakeRequest();

        $initModel->fill($data);

        $initModel->edit();

        return $this->resource($initModel);
    }

    /**
     * 编辑服务器路径
     *
     * @param InitModel $initModel
     * @param FormRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:08
     */
    public function edit(InitModel $initModel, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        $initModel->fill($data);

        $initModel->edit();

        return $this->resource($initModel);
    }

    /**
     * 服务器路径转换
     *
     * @param InitModel $initModel
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/28 13:24
     */
    public function convert(InitModel $initModel, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        $initModels = $this->service->convert($initModel, $data['columns']);

        return response()->json($initModels);
    }

    /**
     * 删除服务器路径
     *
     * @param InitModel $initModel
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:23
     */
    public function delete(InitModel $initModel)
    {
        $initModel->delete();

        return response()->json([]);
    }
}
