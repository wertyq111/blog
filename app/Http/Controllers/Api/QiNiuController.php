<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\QiNiuResource;
use Illuminate\Support\Facades\Storage;


class QiNiuController extends Controller
{
    /**
     * 获取七牛云上传 token
     *
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/7 15:08
     */
    public function upToken()
    {
        $disk = Storage::disk('qiniu');
        $token = $disk->getAdapter()->getUploadToken();

        return (new QiNiuResource(['up-token' => $token]))->response()->setStatusCode(200);
    }

    /**
     * 获取七牛云私有图片地址
     *
     * @param FormRequest $request
     * @return string|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/7/9 14:31
     */
    public function privateUrl(FormRequest $request)
    {
        $url = $request->get('url');
        $percent = $request->get('percent');

        if($url) {
            $picUrl = $url;
            if($percent > 0) {
                $picUrl .= "?imageMogr2/thumbnail/!{$percent}p";
            }
            return $this->resource(['url' => $this->qiniuService->getPrivateUrl($picUrl)]);
        }
    }
}
