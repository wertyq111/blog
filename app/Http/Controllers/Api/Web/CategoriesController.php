<?php

namespace App\Http\Controllers\Api\Web;


use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\Web\CategoryRequest;
use App\Http\Resources\Web\CategoryResource;
use App\Models\Web\Category;
use App\Models\Web\Label;

/**
 * 分类
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2023/6/12
 * Class CategoriesController
 * @package App\Http\Controllers\Api\Web
 */
class CategoriesController extends Controller
{
    /**
     * 分类列表
     *
     *
     * @param FormRequest $request
     * @param Category $category
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/9 10:23
     */
    public function index(FormRequest $request, Category $category)
    {
        $config = [
            'includes' => ['articles', 'labels']
        ];
        $categories = $this->queryBuilder($category, true, $config);

        return CategoryResource::collection($categories);
    }

    /**
     * 分类所有列表
     *
     * @param FormRequest $request
     * @param Category $category
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/9 10:24
     */
    public function list(FormRequest $request, Category $category)
    {
        $config = [
            'includes' => ['articles', 'labels']
        ];
        $categories = $this->queryBuilder($category, true, $config);

        return CategoryResource::collection($categories);
    }

    /**
     * @param Category $category
     * @param Label $label
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 16:11
     */
    public function all(Category $category, Label $label)
    {
        $categories = $category::all()->toArray();
        $labels = $label::all()->toArray();

        return $this->resource(['categories' => $categories, 'labels' => $labels]);
    }

    /**
     * 添加分类
     *
     * @param CategoryRequest $request
     * @param Category $category
     * @return CategoryResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 14:10
     */
    public function add(CategoryRequest $request, Category $category)
    {
        $data = $request->getSnakeRequest();

        $category->fill($data);

        $category->edit();

        return new CategoryResource($category);

    }

    /**
     * 编辑分类
     *
     * @param Category $category
     * @param CategoryRequest $request
     * @return CategoryResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 14:13
     */
    public function edit(Category $category, CategoryRequest $request)
    {
        $category->fill($request->getSnakeRequest());

        $category->edit();

        return new CategoryResource($category);
    }

    /**
     * 删除分类
     *
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/9 09:52
     */
    public function delete(Category $category)
    {
        $category->delete();

        return response()->json([]);
    }
}
