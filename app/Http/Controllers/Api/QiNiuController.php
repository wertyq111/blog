<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QiNiuResource;
use Illuminate\Support\Facades\Storage;


class QiNiuController extends Controller
{
    /**
     * 获取七牛云上传 token
     *
     * @return QiNiuResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/6 10:43
     */
    public function upToken()
    {
        $disk = Storage::disk('qiniu');
        $token = $disk->getAdapter()->getUploadToken();

        return new QiNiuResource(['up-token' => $token]);
    }
}
