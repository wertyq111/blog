<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkDocCategory;

class WorkDocCategoryController extends Controller
{
    /**
     * 牛马文档分类列表 - 分页
     */
    public function index(FormRequest $request, WorkDocCategory $category)
    {
        $allowedFilters = $request->generateAllowedFilters($category->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters,
            'orderBy' => [['sort' => 'asc'], ['id' => 'desc']]
        ];

        $categories = $this->queryBuilder($category, true, $config);

        return $this->resource($categories, ['time' => true, 'collection' => true]);
    }

    /**
     * 牛马文档分类列表 - 不分页
     */
    public function list(FormRequest $request, WorkDocCategory $category)
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
     */
    public function info(WorkDocCategory $category)
    {
        return $this->resource($category);
    }

    /**
     * 添加分类
     */
    public function add(FormRequest $request, WorkDocCategory $category)
    {
        $data = $request->getSnakeRequest();

        if (empty($data['name'])) {
            throw new \Exception('分类名称不能为空');
        }

        $category->fill($data);
        $category->edit();

        return $this->resource($category);
    }

    /**
     * 编辑分类
     */
    public function edit(WorkDocCategory $category, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        if (isset($data['name']) && empty($data['name'])) {
            throw new \Exception('分类名称不能为空');
        }

        $category->fill($data);
        $category->edit();

        return $this->resource($category);
    }

    /**
     * 删除分类
     */
    public function delete(WorkDocCategory $category)
    {
        $category->delete();

        return response()->json([]);
    }
}
