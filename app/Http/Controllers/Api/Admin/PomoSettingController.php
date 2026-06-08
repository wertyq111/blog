<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\PomoSettingRequest;
use App\Http\Resources\BaseResource;
use App\Models\Admin\PomoSetting;

class PomoSettingController extends Controller
{
    /**
     * 读取当前用户番茄钟设置，无则返回默认。
     *
     * @param PomoSetting $pomoSetting
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function show(PomoSetting $pomoSetting)
    {
        $userId = auth('api')->id();

        $setting = PomoSetting::query()->firstOrNew(['create_user' => $userId]);

        return $this->resource($setting);
    }

    /**
     * 保存当前用户番茄钟设置。
     *
     * @param PomoSettingRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function save(PomoSettingRequest $request)
    {
        $userId = auth('api')->id();

        $setting = PomoSetting::query()->firstOrNew(['create_user' => $userId]);

        $setting->fill($request->getSnakeRequest());

        $setting->edit();

        return $this->resource($setting);
    }
}
