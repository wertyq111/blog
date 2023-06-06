<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Web\InfoRequest;
use App\Http\Resources\Web\WebInfoResource;
use App\Models\Web\WebInfo;

class InfoController extends Controller
{
    /**
     * 获取网站信息
     *
     * @param WebInfo $info
     * @return WebInfoResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/6 16:30
     */
    public function index(WebInfo $info)
    {
        return new WebInfoResource($info->find(1));
    }

    public function edit(InfoRequest $request)
    {
        $info = WebInfo::find(1);
        foreach($request->getSnakeRequest() as $key => $value) {
            if(in_array($key, array_keys($info->getAttributes()))) {
                $info->setAttribute($key, $value);
            }
        }

        $info->edit();

        return new WebInfoResource($info);
    }
}
