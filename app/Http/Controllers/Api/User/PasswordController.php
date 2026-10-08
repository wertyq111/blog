<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\User\PasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    /**
     * 修改当前用户密码
     *
     * @param PasswordRequest $request
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/10/8
     */
    public function edit(PasswordRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $user->fill(['password' => $request->validated('password')]);
        $user->edit();

        return response()->json([]);
    }
}
