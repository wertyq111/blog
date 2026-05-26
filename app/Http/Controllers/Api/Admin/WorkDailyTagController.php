<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\WorkDailyTagRequest;
use App\Models\Admin\WorkDailyTag;
use Illuminate\Http\JsonResponse;

class WorkDailyTagController extends Controller
{
    /**
     * 标签列表（系统预设 + 当前用户创建的）
     *
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function list(): JsonResponse
    {
        $user = auth('api')->user();

        $tags = WorkDailyTag::query()
            ->forUser($user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'create_user']);

        return response()->json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => $tags,
        ]);
    }

    /**
     * 新增标签
     *
     * @param WorkDailyTagRequest $request
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function add(WorkDailyTagRequest $request): JsonResponse
    {
        $name = trim((string) $request->input('name'));

        $user = auth('api')->user();

        // 检查是否已存在（系统预设或自己创建的同名标签）
        $exists = WorkDailyTag::query()
            ->forUser($user->id)
            ->where('name', $name)
            ->first();

        if ($exists) {
            return response()->json([
                'code' => 0,
                'msg'  => 'ok',
                'data' => ['id' => $exists->id, 'name' => $exists->name],
            ]);
        }

        $tag = new WorkDailyTag();
        $tag->name = $name;
        $tag->create_user = $user->id;
        $tag->edit();

        return response()->json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => ['id' => $tag->id, 'name' => $tag->name],
        ]);
    }

    /**
     * 删除标签（仅允许删除自己创建的）
     *
     * @param WorkDailyTag $workDailyTag
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function delete(WorkDailyTag $workDailyTag): JsonResponse
    {
        $user = auth('api')->user();

        if ((int) $workDailyTag->create_user === 0) {
            return response()->json(['code' => 403, 'msg' => '系统预设标签不可删除', 'data' => null], 403);
        }

        if ((int) $workDailyTag->create_user !== (int) $user->id) {
            return response()->json(['code' => 403, 'msg' => '无权删除该标签', 'data' => null], 403);
        }

        // 解除关联后删除
        $workDailyTag->logs()->detach();
        $workDailyTag->delete();

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }
}
