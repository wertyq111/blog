<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\AvatarUploadRequest;
use App\Http\Resources\User\UserResource;
use App\Services\Api\User\AvatarService;

class AvatarController extends Controller
{
    public function __construct(private readonly AvatarService $avatarService)
    {
    }

    /**
     * 上传并裁剪当前用户头像。
     *
     * @param AvatarUploadRequest $request
     * @return UserResource
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    public function add(AvatarUploadRequest $request): UserResource
    {
        $profile = $this->avatarService->update(
            auth('api')->user(),
            $request->file('file'),
            $request->validated()
        );

        return new UserResource($profile);
    }
}
