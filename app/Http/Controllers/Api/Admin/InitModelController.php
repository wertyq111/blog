<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\InitModelRequest;
use App\Http\Resources\BaseResource;
use App\Models\Admin\InitModel;
use App\Services\Api\Admin\InitModelService;

class InitModelController extends Controller
{
    /**
     * 初始化模型初始化服务。
     *
     * @param InitModelService $initModelService
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function __construct(private readonly InitModelService $initModelService)
    {
        parent::__construct();
    }


    /**
     * 模型初始化列表 - 分页
     *
     * @param InitModelRequest $request
     * @param InitModel $initModel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function index(InitModelRequest $request, InitModel $initModel)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($initModel->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters,
            'perPage' => $request->perPage(),
        ];
        $initModels = $this->queryBuilder($initModel, true, $config);

        return $this->resource($initModels, ['time' => true, 'collection' => true]);
    }

    /**
     * 模型初始化详情
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
     * 添加模型初始化
     *
     * @param InitModelRequest $request
     * @param InitModel $initModel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function add(InitModelRequest $request, InitModel $initModel)
    {
        $data = $request->getSnakeRequest();

        $initModel->fill($data);

        $initModel->edit();

        return $this->resource($initModel);
    }

    /**
     * 编辑模型初始化
     *
     * @param InitModel $initModel
     * @param InitModelRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function edit(InitModel $initModel, InitModelRequest $request)
    {
        $data = $request->getSnakeRequest();

        $initModel->fill($data);

        $initModel->edit();

        return $this->resource($initModel);
    }

    /**
     * 模型初始化转换
     *
     * @param InitModel $initModel
     * @param InitModelRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function convert(InitModel $initModel, InitModelRequest $request)
    {
        $data = $request->validated();

        $initModels = $this->initModelService->convert($initModel, $data['columns']);

        return response()->json($initModels);
    }

    /**
     * 删除模型初始化
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

    /**
     * 兼容旧版前端批量删除
     *
     * @param InitModelRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function batchDelete(InitModelRequest $request)
    {
        InitModel::query()->whereIn('id', $request->integerIds())->delete();

        return response()->json([]);
    }
}
