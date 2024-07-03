<?php

namespace App\Http\Controllers\Api\MiniProgram;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\MiniProgram\WallpaperRequest;
use App\Http\Resources\BaseResource;
use App\Models\MiniProgram\Wallpaper;
use App\Services\Api\MiniProgram\WallpaperService;

class WallpaperController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new WallpaperService();
    }


    /**
     * 壁纸列表
     *
     * @param FormRequest $request
     * @param Wallpaper $wallpaper
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 14:33
     */
    public function index(FormRequest $request, Wallpaper $wallpaper)
    {
        $data = $request->all();

        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($wallpaper->getRequestFilters());

        $config = [
            'includes' => ['classify'],
            'allowedFilters' => $allowedFilters,
            'perPage' => $data['perPage'] ?? null,
            'orderBy' => $data['orderBy'] ?? null
        ];
        $wallpapers = $this->queryBuilder($wallpaper, true, $config);

        foreach ($wallpapers as &$wallpaper) {
            $wallpaper['url'] = strstr($wallpaper['url'], env("QINIU_DOMAIN", null)) ? $wallpaper['url'] : "https://" . env("QINIU_DOMAIN", null) . "/" . $wallpaper['url'];
            $wallpaper['small_pic_url'] = $wallpaper['url'] . "?imageMogr2/thumbnail/!30p";
            $wallpaper['url'] = $this->qiniuService->getPrivateUrl($wallpaper['url']);
            $wallpaper['small_pic_url'] = $this->qiniuService->getPrivateUrl($wallpaper['small_pic_url']);
            $wallpaper['tags'] = json_decode($wallpaper['tags'], true);
        }

        return $this->resource($wallpapers, ['time' => true, 'collection' => true]);
    }

    /**
     * 当前用户壁纸列表
     *
     * @param FormRequest $request
     * @param Wallpaper $wallpaper
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 14:33
     */
    public function userList(FormRequest $request, Wallpaper $wallpaper)
    {
        $data = $request->all();

        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($wallpaper->getRequestFilters());

        $config = [
            'includes' => ['classify'],
            'allowedFilters' => $allowedFilters,
            'perPage' => $data['perPage'] ?? null,
            'orderBy' => $data['orderBy'] ?? null
        ];
        $wallpapers = $this->queryBuilder($wallpaper, true, $config);

        foreach ($wallpapers as &$wallpaper) {
            $wallpaper['url'] = strstr($wallpaper['url'], env("QINIU_DOMAIN", null)) ? $wallpaper['url'] : "https://" . env("QINIU_DOMAIN", null) . "/" . $wallpaper['url'];
            $wallpaper['small_pic_url'] = $wallpaper['url'] . "?imageMogr2/thumbnail/!30p";
            $wallpaper['url'] = $this->qiniuService->getPrivateUrl($wallpaper['url']);
            $wallpaper['small_pic_url'] = $this->qiniuService->getPrivateUrl($wallpaper['small_pic_url']);
            $wallpaper['tags'] = json_decode($wallpaper['tags'], true);
        }

        return $this->resource($wallpapers, ['time' => true, 'collection' => true]);
    }

    /**
     * 菜壁纸详情
     *
     * @param Wallpaper $wallpaper
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 14:35
     */
    public function info(Wallpaper $wallpaper)
    {
        $wallpaper->url = $this->qiniuService->getPrivateUrl(
            strstr($wallpaper->url, env("QINIU_DOMAIN", null)) ? $wallpaper->url : "https://" . env("QINIU_DOMAIN", null) . "/" . $wallpaper->url
        );

        return $this->resource($wallpaper);
    }

    /**
     * 获取随机 9 张壁纸
     *
     * @param Wallpaper $wallpaper
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/9 14:06
     */
    public function random(Wallpaper $wallpaper)
    {
        $wallpapers = $wallpaper->inRandomOrder()->limit(9)->get();

        foreach ($wallpapers as &$wallpaper) {
            $wallpaper['url'] = strstr($wallpaper['url'], env("QINIU_DOMAIN", null)) ? $wallpaper['url'] : "https://" . env("QINIU_DOMAIN", null) . "/" . $wallpaper['url'];
            $wallpaper['small_pic_url'] = $wallpaper['url'] . "?imageMogr2/thumbnail/!30p";
            $wallpaper['url'] = $this->qiniuService->getPrivateUrl($wallpaper['url']);
            $wallpaper['small_pic_url'] = $this->qiniuService->getPrivateUrl($wallpaper['small_pic_url']);
            $wallpaper['tags'] = json_decode($wallpaper['tags'], true);
        }
        unset($wallpaper);

        return $this->resource($wallpapers);
    }

    /**
     * 添加壁纸
     *
     * @param WallpaperRequest $request
     * @param Wallpaper $wallpaper
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 14:39
     */
    public function add(WallpaperRequest $request, Wallpaper $wallpaper)
    {
        $data = $request->getSnakeRequest();

        if (isset($data['tags'])) {
            if (is_array($data['tags']) && count($data['tags']) > 0) {
                // tags 转换成json
                $data['tags'] = json_encode($data['tags']);
            } else {
                $data['tags'] = "[]";
            }
        } else {
            $data['tags'] = "[]";
        }

        if (isset($data['score']) && !is_int($data['score'])) {
            $data['score'] = 0;
        }

        $wallpaper->fill($data);

        $wallpaper->edit();

        return $this->resource($wallpaper);
    }

    /**
     * 编辑壁纸
     *
     * @param Wallpaper $wallpaper
     * @param FormRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 15:57
     */
    public function edit(Wallpaper $wallpaper, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        $imagePath = $wallpaper->url != $data['url']
            ? str_replace("https://" . env("QINIU_DOMAIN", null) . "/", "", $wallpaper->url)
            : null;

        if (isset($data['tags'])) {
            if (is_array($data['tags']) && count($data['tags']) > 0) {
                // tags 转换成json
                $data['tags'] = json_encode($data['tags']);
            } else {
                $data['tags'] = "[]";
            }
        } else {
            $data['tags'] = "[]";
        }

        if (isset($data['score']) && !is_int($data['score'])) {
            $data['score'] = 0;
        }

        $wallpaper->fill($data);

        $wallpaper->edit();


        // 删除原先的图片
        if ($imagePath !== null) {
            $this->qiniuService->delete($imagePath);
        }

//        list($success, $error) = $this->qiniuService->delete($imagePath);
//        if($error) {
//            throw new \Exception($error->message());
//        }

        return $this->resource($wallpaper);
    }

    /**
     * 下载壁纸
     *
     * @param Wallpaper $wallpaper
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:50
     */
    public function download(Wallpaper $wallpaper)
    {
        try {
            // 获取当前登录会员信息
            $user = auth()->user();
            $member = $user->member;

            // 保存到壁纸下载表
            $data = [
                'member_id' => $member->id,
                'wallpaper_id' => $wallpaper->id,
                'num' => 1
            ];
            $wallpaperDownload = $this->service->download($data);

            return $this->resource($wallpaperDownload);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 壁纸频分
     *
     * @param Wallpaper $wallpaper
     * @param FormRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/9 14:32
     */
    public function score(Wallpaper $wallpaper, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        try {
            // 获取当前登录会员信息
            $user = auth()->user();
            $member = $user->member;

            // 保存到壁纸评分表
            $data = [
                'member_id' => $member->id,
                'wallpaper_id' => $wallpaper->id,
                'score' => $data['score']
            ];
            $wallpaperScore = $this->service->calculateScore($data);

            return $this->resource($wallpaperScore);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 删除壁纸
     *
     * @param Wallpaper $wallpaper
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 15:57
     */
    public function delete(Wallpaper $wallpaper)
    {
        $wallpaper->delete();

        $imagePath = str_replace("https://" . env("QINIU_DOMAIN", null) . "/", "", $wallpaper->url);

        // 删除原先的图片
        if ($imagePath !== null) {
            $this->qiniuService->delete($imagePath);
        }

        return response()->json([]);
    }
}
