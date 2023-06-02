<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * 设置请求成功时 HTTP 状态值
     * @param $request
     * @param $response
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/2 15:12
     */
    public function withResponse($request, $response)
    {
        /**
         * Not all prerequisites were met.
         */
        $response->setStatusCode(201);
    }
}
