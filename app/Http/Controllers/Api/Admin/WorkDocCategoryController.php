<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkDocCategory;
use Illuminate\Support\Facades\DB;

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

        $categories = $query->orderBy('sort', 'asc')->orderBy('id', 'asc')->get();

        $nodes = [];
        foreach ($categories as $item) {
            $row = $item->toArray();
            $row['children'] = [];
            $nodes[$item->id] = $row;
        }

        $tree = [];
        foreach ($nodes as $id => &$node) {
            $parentId = (int)($node['parent_id'] ?? 0);
            if ($parentId > 0 && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $this->resource($tree);
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
     * 分类拖拽排序
     */
    public function reorder(FormRequest $request, WorkDocCategory $category)
    {
        $order = $request->input('order', $request->input('list', []));

        if (!is_array($order) || empty($order)) {
            throw new \Exception('排序数据不能为空');
        }

        DB::transaction(function () use ($order, $category) {
            foreach ($order as $item) {
                if (!isset($item['id'])) {
                    continue;
                }
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
     */
    public function delete(WorkDocCategory $category)
    {
        $category->delete();

        return response()->json([]);
    }
}
