<?php

namespace App\Http\Controllers\Api\MiniProgram;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\MiniProgram\PhotoCategoryRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\MiniProgram\PhotoResource;
use App\Models\MiniProgram\PhotoCategory;
use App\Services\Api\MiniProgram\PhotoService;

class PhotoCategoryController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new PhotoService();
    }

    /**
     * 相册分类列表
     *
     * @param FormRequest $request
     * @param PhotoCategory $category
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 08:59
     */
    public function index(FormRequest $request, PhotoCategory $category)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($category->getRequestFilters());

        $config = [
            'includes' => ['member'],
            'allowedFilters' => $allowedFilters,
            'conditions' => $this->authorizeForMember()
        ];
        $categories = $this->queryBuilder($category, true, $config);

        return $this->resource($categories, ['time' => true, 'collection' => true]);
    }

    /**
     * 获取壁纸分类
     *
     * @param FormRequest $request
     * @param PhotoCategory $category
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/9 15:24
     */
    public function list(FormRequest $request, PhotoCategory $category)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($category->getRequestFilters());

        $config = [
            'includes' => ['member'],
            'allowedFilters' => $allowedFilters,
            'conditions' => $this->authorizeForMember()
        ];
        $classifies = $this->queryBuilder($category, false, $config);

        return $this->resource($classifies, ['time' => true, 'collection' => true]);
    }

    /**
     * 相册分类详情
     *
     * @param PhotoCategory $category
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 14:43
     */
    public function info(PhotoCategory $category)
    {
        $this->authorize('update', $category);

        $category->num = count($category->photos);
        //$category->photosList = [];
        foreach($category->photos as $photo) {
            $photo->date = $photo->created_at;
        }
        $photos = $this->service->sift($category->photos);

        // 根据创建日期进行排序
        $photosTemp = array_column($photos,'date'); //返回数组中指定的一列
        array_multisort($photosTemp,SORT_DESC, $photos); //对多个数组或多维数组进行排序
        $category->photosList = $photos;

        return $this->resource($category, true);
    }

    /**
     * 添加相册分类
     *
     * @param PhotoCategoryRequest $request
     * @param PhotoCategory $category
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 09:14
     */
    public function add(PhotoCategoryRequest $request, PhotoCategory $category)
    {
        $data = $request->getSnakeRequest();
        // 补充会员信息
        $data = array_merge($data, $this->authorizeForMember());

        $category->fill($data);

        $category->edit();

        return $this->resource($category);
    }

    /**
     * 编辑相册分类
     *
     * @param PhotoCategory $category
     * @param FormRequest $request
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 09:15
     */
    public function edit(PhotoCategory $category, FormRequest $request)
    {
        $this->authorize('update', $category);

        $data = $request->getSnakeRequest();

        $category->fill($data);

        $category->edit();

        return $this->resource($category);
    }

    /**
     * 删除相册分类
     *
     * @param PhotoCategory $category
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 09:14
     */
    public function delete(PhotoCategory $category)
    {
        $this->authorize('update', $category);

        if(count($category->photos) == 0) {
            $category->delete();
        }

        return response()->json([]);
    }

    /**
     * 最新相册
     *
     * @param FormRequest $request
     * @param PhotoCategory $category
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/31 16:47
     */
    public function new(FormRequest $request, PhotoCategory $category)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($category->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters,
            'orderBy' => [['sort' => 'asc'], ['updated_at' => 'desc']],
            'conditions' => $this->authorizeForMember()
        ];

        $categories = $this->queryBuilder($category, false, $config);

        foreach($categories as $category) {
            if(count($category->photos) > 0) {
                $category->photoList = $this->service->sift($category->photos);
                $category->photo = $category->photoList[count($category->photos) - 1];
            }
        }

        return $this->resource($categories, ['time' => true, 'collection' => true]);
    }

    /**
     * 校验相册分类
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/6 15:36
     */
    public function check(FormRequest $request)
    {
        $name = $request->get('name');

        $photoCategory = $this->memberExistCheck(PhotoCategory::class, ['name' => $name]);

        return $photoCategory && $name
            ? $this->resource($photoCategory, ['time' => true, 'collection' => true])
            : response()->json([]);
    }

}
