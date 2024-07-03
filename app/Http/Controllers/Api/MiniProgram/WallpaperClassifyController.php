<?php

namespace App\Http\Controllers\Api\MiniProgram;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\MiniProgram\WallpaperClassifyRequest;
use App\Http\Resources\BaseResource;
use App\Models\MiniProgram\WallpaperClassify;

class WallpaperClassifyController extends Controller
{
    /**
     * 壁纸列表
     *
     * @param FormRequest $request
     * @param WallpaperClassify $classify
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 14:33
     */
    public function index(FormRequest $request, WallpaperClassify $classify)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($classify->getRequestFilters());

        $config = [
            'includes' => ['wallpapers'],
            'allowedFilters' => $allowedFilters
        ];
        $classifies = $this->queryBuilder($classify, true, $config);

        foreach($classifies as &$classify) {
            $classify['pic_url'] = strstr($classify['pic_url'], env("QINIU_DOMAIN", null)) ? $classify['pic_url'] : "https://" . env("QINIU_DOMAIN", null) . "/" . $classify['pic_url'];
            $classify['pic_url'] = $this->qiniuService->getPrivateUrl($classify['pic_url']);
        }
        unset($classify);

        return $this->resource($classifies, ['time' => true, 'collection' => true]);
    }

    /**
     * 获取壁纸分类
     *
     * @param FormRequest $request
     * @param WallpaperClassify $classify
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/9 15:24
     */
    public function list(FormRequest $request, WallpaperClassify $classify)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($classify->getRequestFilters());

        $config = [
            'includes' => ['wallpapers'],
            'allowedFilters' => $allowedFilters
        ];
        $classifies = $this->queryBuilder($classify, false, $config);

        foreach($classifies as &$classify) {
            $classify['pic_url'] = strstr($classify['pic_url'], env("QINIU_DOMAIN", null)) ? $classify['pic_url'] : "https://" . env("QINIU_DOMAIN", null) . "/" . $classify['pic_url'];
            $classify['pic_url'] = $this->qiniuService->getPrivateUrl($classify['pic_url']);
        }
        unset($classify);

        return $this->resource($classifies, ['time' => true, 'collection' => true]);
    }

    /**
     * 菜壁纸分类详情
     *
     * @param WallpaperClassify $classify
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 14:35
     */
    public function info(WallpaperClassify $classify)
    {
        return $this->resource($classify);
    }

    /**
     * 添加壁纸分类
     *
     * @param WallpaperClassifyRequest $request
     * @param WallpaperClassify $classify
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 14:39
     */
    public function add(WallpaperClassifyRequest $request, WallpaperClassify $classify)
    {
        $data = $request->getSnakeRequest();

        $classify->fill($data);

        $classify->edit();

        return $this->resource($classify);
    }

    /**
     * 编辑壁纸分类
     *
     * @param WallpaperClassify $classify
     * @param FormRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 16:01
     */
    public function edit(WallpaperClassify $classify, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        $imagePath = $classify->pic_url != $data['pic_url']
            ? str_replace("https://". env("QINIU_DOMAIN", null). "/", "", $classify->pic_url)
            : null;

        $classify->fill($data);

        $classify->edit();

        // 删除原先的图片
        if($imagePath !== null) {
            $this->qiniuService->delete($imagePath);
        }

        return $this->resource($classify);
    }

    /**
     * 删除壁纸分类
     *
     * @param WallpaperClassify $classify
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 16:00
     */
    public function delete(WallpaperClassify $classify)
    {
        if(count($classify->wallpapers) == 0) {
            $classify->delete();

            $imagePath = str_replace("https://". env("QINIU_DOMAIN", null). "/", "", $classify->pic_url);

            // 删除原先的图片
            if($imagePath !== null) {
                $this->qiniuService->delete($imagePath);
            }
        }

        return response()->json([]);
    }
}
