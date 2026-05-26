<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\TodoItemRequest;
use App\Models\Admin\TodoItem;
use Spatie\QueryBuilder\QueryBuilder;

class TodoItemController extends Controller
{
    /**
     * 待办列表 - 分页。
     *
     * @param TodoItemRequest $request
     * @param TodoItem $todoItem
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function index(TodoItemRequest $request, TodoItem $todoItem)
    {
        $allowedFilters = $request->generateAllowedFilters($todoItem->getRequestFilters());

        $query = QueryBuilder::for($todoItem->newQuery()->with('platform'))
            ->allowedFilters($allowedFilters);

        foreach ($this->getAuthorizeConditions() as $condition) {
            $query->where($condition[0], $condition[1], $condition[2]);
        }

        $keyword = $request->get('keyword');
        if (!empty($keyword)) {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        if ($startDate && $endDate) {
            $query->where('due_date', '>=', $startDate)
                ->where('due_date', '<=', $endDate);
        }

        $query->orderBy('status', 'asc')
            ->orderBy('priority', 'desc')
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc');

        $items = $query->paginate($request->perPage());

        return $this->resource($items, ['time' => true, 'collection' => true]);
    }

    /**
     * 待办详情。
     *
     * @param TodoItem $todoItem
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function info(TodoItem $todoItem)
    {
        $this->authorizeOwner($todoItem);
        $todoItem->load('platform');

        return $this->resource($todoItem, ['time' => true]);
    }

    /**
     * 添加待办。
     *
     * @param TodoItemRequest $request
     * @param TodoItem $todoItem
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function add(TodoItemRequest $request, TodoItem $todoItem)
    {
        $data = $request->getSnakeRequest();

        $todoItem->fill($data);
        $todoItem->edit();
        $todoItem->load('platform');

        return $this->resource($todoItem);
    }

    /**
     * 编辑待办。
     *
     * @param TodoItem $todoItem
     * @param TodoItemRequest $request
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function edit(TodoItem $todoItem, TodoItemRequest $request)
    {
        $this->authorizeOwner($todoItem);
        $data = $request->getSnakeRequest();

        $todoItem->fill($data);
        $todoItem->edit();
        $todoItem->load('platform');

        return $this->resource($todoItem);
    }

    /**
     * 删除待办。
     *
     * @param TodoItem $todoItem
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function delete(TodoItem $todoItem)
    {
        $this->authorizeOwner($todoItem);
        $todoItem->delete();

        return response()->json([]);
    }

    /**
     * 修改待办状态。
     *
     * @param TodoItem $todoItem
     * @param TodoItemRequest $request
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function updateStatus(TodoItem $todoItem, TodoItemRequest $request)
    {
        $this->authorizeOwner($todoItem);

        $todoItem->status = (int)$request->get('status');
        $todoItem->edit();

        return $this->resource($todoItem);
    }

    /**
     * 待办统计。
     *
     * @param TodoItemRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function statistics(TodoItemRequest $request)
    {
        $query = TodoItem::query();
        foreach ($this->getAuthorizeConditions() as $condition) {
            $query->where($condition[0], $condition[1], $condition[2]);
        }

        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', 0)->count();
        $inProgress = (clone $query)->where('status', 1)->count();
        $completed = (clone $query)->where('status', 2)->count();
        $canceled = (clone $query)->where('status', 3)->count();

        return response()->json([
            'code' => 0,
            'msg' => '',
            'data' => compact('total', 'pending', 'inProgress', 'completed', 'canceled')
        ]);
    }

    /**
     * 获取当前用户数据范围。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function getAuthorizeConditions(): array
    {
        $user = auth('api')->user();
        $isManager = false;
        foreach ($user->roles as $role) {
            if ($role->code === 'super') {
                $isManager = true;
                break;
            }
        }

        if ($isManager) {
            return [];
        }

        return [['create_user', '=', $user->id]];
    }

    /**
     * 校验待办归属权限。
     *
     * @param TodoItem $todoItem
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function authorizeOwner(TodoItem $todoItem): void
    {
        $user = auth('api')->user();
        $isManager = false;
        foreach ($user->roles as $role) {
            if ($role->code === 'super') {
                $isManager = true;
                break;
            }
        }

        if (!$isManager && $todoItem->create_user != $user->id) {
            throw new \Exception('无权限操作该记录');
        }
    }
}
