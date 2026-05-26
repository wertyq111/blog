<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\WorkDocCategoryRequest;
use App\Models\Admin\WorkDocCategory;
use Illuminate\Support\Facades\DB;

class WorkDocCategoryController extends Controller
{
    /**
     * 牛马文档分类列表 - 分页
     *
     * @param WorkDocCategoryRequest $request
     * @param WorkDocCategory $category
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function index(WorkDocCategoryRequest $request, WorkDocCategory $category)
    {
        $allowedFilters = $request->generateAllowedFilters($category->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters,
            'perPage' => $request->perPage(),
            'orderBy' => [['sort' => 'asc'], ['id' => 'desc']]
        ];

        $categories = $this->queryBuilder($category, true, $config);

        return $this->resource($categories, ['time' => true, 'collection' => true]);
    }

    /**
     * 牛马文档分类列表 - 不分页
     *
     * @param WorkDocCategoryRequest $request
     * @param WorkDocCategory $category
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function list(WorkDocCategoryRequest $request, WorkDocCategory $category)
    {
        $query = $category->newQuery();

        if ($request->get('status') !== null) {
            $query->where('status', $request->get('status'));
        }

        $categories = $query->orderBy('sort', 'asc')->orderBy('id', 'desc')->get();

        return $this->resource($categories);
    }

    /**
     * 分类详情
     *
     * @param WorkDocCategory $category
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function info(WorkDocCategory $category)
    {
        return $this->resource($category);
    }

    /**
     * 添加分类
     *
     * @param WorkDocCategoryRequest $request
     * @param WorkDocCategory $category
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function add(WorkDocCategoryRequest $request, WorkDocCategory $category)
    {
        $data = $request->getSnakeRequest();

        $category->fill($data);
        $category->edit();

        return $this->resource($category);
    }

    /**
     * 编辑分类
     *
     * @param WorkDocCategory $category
     * @param WorkDocCategoryRequest $request
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function edit(WorkDocCategory $category, WorkDocCategoryRequest $request)
    {
        $data = $request->getSnakeRequest();

        $category->fill($data);
        $category->edit();

        return $this->resource($category);
    }

    /**
     * 分类拖拽排序
     *
     * @param WorkDocCategoryRequest $request
     * @param WorkDocCategory $category
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function reorder(WorkDocCategoryRequest $request, WorkDocCategory $category)
    {
        $order = $request->input('order', $request->input('list', []));

        DB::transaction(function () use ($order, $category) {
            foreach ($order as $item) {
                $category->newQuery()->where('id', $item['id'])->update([
                    'parent_id' => isset($item['parent_id']) ? (int)$item['parent_id'] : 0,
                    'sort' => isset($item['sort']) ? (int)$item['sort'] : 0,
                ]);
            }
        });

        return response()->json(['code' => 0, 'msg' => '分类排序已保存']);
    }

    /**
     * 删除分类
     *
     * @param WorkDocCategory $category
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function delete(WorkDocCategory $category)
    {
        $category->delete();

        return response()->json([]);
    }
}
