<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\PomoTaskRequest;
use App\Http\Resources\BaseResource;
use App\Models\Admin\PomoTask;
use Spatie\QueryBuilder\QueryBuilder;

class PomoTaskController extends Controller
{
    /**
     * 当前用户任务列表 - 分页。
     *
     * @param PomoTaskRequest $request
     * @param PomoTask $pomoTask
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function index(PomoTaskRequest $request, PomoTask $pomoTask)
    {
        $allowedFilters = $request->generateAllowedFilters($pomoTask->getRequestFilters());

        $tasks = QueryBuilder::for($pomoTask)
            ->where('create_user', auth('api')->id())
            ->allowedFilters($allowedFilters)
            ->orderBy('done', 'asc')
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($request->perPage());

        return $this->resource($tasks, ['time' => true, 'collection' => true]);
    }

    /**
     * 任务详情。
     *
     * @param PomoTask $pomoTask
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function info(PomoTask $pomoTask)
    {
        $this->authorizeOwner($pomoTask);

        return $this->resource($pomoTask);
    }

    /**
     * 新增任务。
     *
     * @param PomoTaskRequest $request
     * @param PomoTask $pomoTask
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function add(PomoTaskRequest $request, PomoTask $pomoTask)
    {
        $pomoTask->fill($request->getSnakeRequest());

        $pomoTask->edit();

        return $this->resource($pomoTask);
    }

    /**
     * 编辑任务。
     *
     * @param PomoTask $pomoTask
     * @param PomoTaskRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function edit(PomoTask $pomoTask, PomoTaskRequest $request)
    {
        $this->authorizeOwner($pomoTask);

        $pomoTask->fill($request->getSnakeRequest());

        $pomoTask->edit();

        return $this->resource($pomoTask);
    }

    /**
     * 切换完成状态。
     *
     * @param PomoTask $pomoTask
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function toggleDone(PomoTask $pomoTask)
    {
        $this->authorizeOwner($pomoTask);

        $pomoTask->done = $pomoTask->done ? 0 : 1;

        $pomoTask->edit();

        return $this->resource($pomoTask);
    }

    /**
     * 已完成番茄数 +1。
     *
     * @param PomoTask $pomoTask
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function increment(PomoTask $pomoTask)
    {
        $this->authorizeOwner($pomoTask);

        $pomoTask->completed_pomos = $pomoTask->completed_pomos + 1;

        $pomoTask->edit();

        return $this->resource($pomoTask);
    }

    /**
     * 删除任务。
     *
     * @param PomoTask $pomoTask
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function delete(PomoTask $pomoTask)
    {
        $this->authorizeOwner($pomoTask);

        $pomoTask->delete();

        return response()->json([]);
    }

    /**
     * 批量删除当前用户任务。
     *
     * @param PomoTaskRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function batchDelete(PomoTaskRequest $request)
    {
        PomoTask::query()
            ->where('create_user', auth('api')->id())
            ->whereIn('id', $request->integerIds())
            ->delete();

        return response()->json([]);
    }

    /**
     * 校验任务归属当前用户（super 角色放行）。
     *
     * @param PomoTask $pomoTask
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    private function authorizeOwner(PomoTask $pomoTask): void
    {
        $user = auth('api')->user();

        $isManager = false;
        foreach ($user->roles as $role) {
            if ($role->code === 'super') {
                $isManager = true;
            }
        }

        if (!$isManager && $pomoTask->create_user != $user->id) {
            abort(403, '无权操作此任务');
        }
    }
}
