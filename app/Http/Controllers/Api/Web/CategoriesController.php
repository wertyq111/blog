<?php

namespace App\Http\Controllers\Api\Web;


use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Web\CategoryRequest;
use App\Http\Resources\Web\CategoryResource;
use App\Models\Web\Category;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

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
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 15:44
     */
    public function index(Request $request)
    {
        $categories = QueryBuilder::for(Category::class)->paginate();
        return CategoryResource::collection($categories);
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
        $category->type = $data['type'];

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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 14:28
     */
    public function delete(Category $category)
    {
        $category->delete();

        return response(null, 204);
    }


}
