<?php

namespace App\Http\Controllers\Api\MiniProgram;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\MiniProgram\PhotoRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\MiniProgram\PhotoResource;
use App\Models\MiniProgram\Photo;
use App\Services\Api\MiniProgram\PhotoService;

class PhotoController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new PhotoService();
    }


    /**
     * 相册列表
     *
     * @param FormRequest $request
     * @param Photo $photo
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 14:33
     */
    public function index(FormRequest $request, Photo $photo)
    {
        $data = $request->all();

        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($photo->getRequestFilters());

        $config = [
            'includes' => ['category', 'member'],
            'allowedFilters' => $allowedFilters,
            'perPage' => $data['perPage'] ?? null,
            'orderBy' => $data['orderBy'] ?? null,
            'conditions' => $this->authorizeForMember()
        ];
        $photos = $this->queryBuilder($photo, true, $config);

        foreach ($photos as &$photo) {
            $photo['url'] = strstr($photo['url'], env("QINIU_DOMAIN", null)) ? $photo['url'] : "https://" . env("QINIU_DOMAIN", null) . "/" . $photo['url'];
            $photo['small_pic_url'] = $photo['url'] . "?imageMogr2/thumbnail/!30p";
            $photo['url'] = $this->qiniuService->getPrivateUrl($this->service->convertFormat($photo['url']));
            $photo['small_pic_url'] = $this->qiniuService->getPrivateUrl($photo['small_pic_url']);
            $photo['tags'] = json_decode($photo['tags'], true);
        }

        return $this->resource($photos, ['time' => true, 'collection' => true]);
    }

    /**
     * 照片详情
     *
     * @param Photo $photo
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 15:13
     */
    public function info(Photo $photo)
    {
        $this->authorize('update', $photo);

        $photo->url = $this->qiniuService->getPrivateUrl(
            strstr($photo->url, env("QINIU_DOMAIN", null)) ? $photo->url : "https://" . env("QINIU_DOMAIN", null) . "/" . $photo->url
        );

        return $this->resource($photo);
    }

    /**
     * 添加相册
     *
     * @param PhotoRequest $request
     * @param Photo $photo
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 14:39
     */
    public function add(PhotoRequest $request, Photo $photo)
    {
        $data = $request->getSnakeRequest();

        $urls = $data['url'];

        if(is_array($urls)) {
            foreach($urls as $url) {
                $newData = $data;
                $newData['url'] = $url;
                $photo = new Photo();
                $photo = $this->service->add($photo, $newData);
            }
        } else {
            $photo = $this->service->add($photo, $data);
        }

        return $this->resource($photo);
    }

    /**
     * 编辑照片
     *
     * @param Photo $photo
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 15:14
     */
    public function edit(Photo $photo, FormRequest $request)
    {
        $this->authorize('update', $photo);

        $data = $request->getSnakeRequest();

        if(isset($data['url'])) {
            $imagePath = $photo->url != $data['url']
                ? preg_replace("/http(s):\/\/" . env("QINIU_DOMAIN", null) . "\//", "", $photo->url)
                : null;
        }

        $photo->fill($data);

        $photo->edit();

        if(isset($data['url'])) {
            // 删除原先的图片
            if ($imagePath !== null) {
                $this->qiniuService->delete($imagePath);
            }
        }

        return $this->resource($photo);
    }

    /**
     * 删除照片
     *
     * @param Photo $photo
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/8 15:57
     */
    public function delete(Photo $photo)
    {
        $this->authorize('update', $photo);

        // 物理删除
        $photo->forceDelete();

        $imagePath = preg_replace("/http(s):\/\/" . env("QINIU_DOMAIN", null) . "\//", "", $photo->url);

        // 删除原先的图片
        if ($imagePath !== null) {
            $result = $this->qiniuService->delete($imagePath);
        }

        return response()->json([]);
    }

    /**
     * 批量删除照片
     *
     * @param FormRequest $request
     * @param Photo $photo
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/12 14:02
     */
    public function batchDelete(FormRequest $request, Photo $photo)
    {
        $ids = $request->get('id');
        foreach($ids as $id) {
            $this->delete($photo->find($id));
        }

        return response()->json([]);
    }

    /**
     * 精制照片
     *
     * @param FormRequest $request
     * @param Photo $photo
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/31 17:02
     */
    public function refine(FormRequest $request, Photo $photo)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($photo->getRequestFilters());

        $config = [
            'includes' => ['member'],
            'allowedFilters' => $allowedFilters,
            'conditions' => $this->authorizeForMember()
                ? array_merge($this->authorizeForMember(), ['show' => true]) : ['show' => true]
        ];

        $photos = $this->queryBuilder($photo, false, $config);

        foreach($photos as $photo) {
            $photo->url = $this->qiniuService->getPrivateUrl(
                $this->service->convertFormat(
                    strstr($photo->url, env("QINIU_DOMAIN", null)) ? $photo->url : "https://" . env("QINIU_DOMAIN", null) . "/" . $photo->url
                )
            );
        }

        return PhotoResource::collection($photos);
    }
}
