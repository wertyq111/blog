<?php

namespace App\Http\Controllers\Api\MiniProgram;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\MiniProgram\MaterialRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\MiniProgram\MaterialResource;
use App\Models\MiniProgram\Material;
use App\Services\Api\MiniProgram\MaterialService;

class MaterialController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new MaterialService();
    }


    /**
     * 列表
     *
     * @param FormRequest $request
     * @param Material $material
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 14:33
     */
    public function index(FormRequest $request, Material $material)
    {
        $data = $request->all();

        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($material->getRequestFilters());

        $config = [
            'includes' => ['member', 'parent', 'house'],
            'allowedFilters' => $allowedFilters,
            'perPage' => $data['perPage'] ?? null,
            'orderBy' => $data['orderBy'] ?? null,
            'conditions' => $this->authorizeForMember()
        ];
        $materials = $this->queryBuilder($material, true, $config);

        return $this->resource($materials, ['time' => true, 'collection' => true]);
    }

    /**
     * 所有列表
     *
     * @param FormRequest $request
     * @param Material $material
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/18 10:12
     */
    public function list(FormRequest $request, Material $material)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($material->getRequestFilters());

        $config = [
            'includes' => ['member', 'parent', 'house'],
            'allowedFilters' => $allowedFilters,
            'conditions' => $this->authorizeForMember()
        ];
        $materials = $this->queryBuilder($material, false, $config);

        return $this->resource($materials, ['time' => true, 'collection' => true]);
    }

    /**
     * 详情
     *
     * @param Material $material
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 15:13
     */
    public function info(Material $material)
    {
        $this->authorize('update', $material);

        return $this->resource($material);
    }

    /**
     * 添加
     *
     * @param MaterialRequest $request
     * @param Material $material
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 14:39
     */
    public function add(MaterialRequest $request, Material $material)
    {
        $data = $request->getSnakeRequest();

        $material = $this->service->add($material, $data);

        return $this->resource($material);
    }

    /**
     * 编辑
     *
     * @param Material $material
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 15:14
     */
    public function edit(Material $material, FormRequest $request)
    {
        $this->authorize('update', $material);

        $data = $request->getSnakeRequest();

        $material->fill($data);

        $material->edit();

        return $this->resource($material);
    }

    /**
     * 删除
     *
     * @param Material $material
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 15:57
     */
    public function delete(Material $material)
    {
        $this->authorize('delete', $material);

        $material->delete();

        return response()->json([]);
    }

    /**
     * 批量删除
     *
     * @param FormRequest $request
     * @param Material $material
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/12 14:02
     */
    public function batchDelete(FormRequest $request, Material $material)
    {
        $ids = $request->get('id');
        foreach($ids as $id) {
            $this->delete($material->find($id));
        }

        return response()->json([]);
    }

    /**
     * 校验
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/19 13:51
     */
    public function check(FormRequest $request)
    {
        $name = $request->get('name');

        $material = $this->memberExistCheck(Material::class, ['name' => $name]);

        return $material && $name
            ? $this->resource($material, ['time' => true, 'collection' => true])
            : response()->json([]);
    }
}
