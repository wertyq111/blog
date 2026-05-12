<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\TodoItem;
use Spatie\QueryBuilder\QueryBuilder;

class TodoItemController extends Controller
{
    public function index(FormRequest $request, TodoItem $todoItem)
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

        $items = $query->paginate($request->get('pageSize') ?? self::PER_PAGE);

        return $this->resource($items, ['time' => true, 'collection' => true]);
    }

    public function info(TodoItem $todoItem)
    {
        $this->authorizeOwner($todoItem);
        $todoItem->load('platform');

        return $this->resource($todoItem, ['time' => true]);
    }

    public function add(FormRequest $request, TodoItem $todoItem)
    {
        $data = $this->normalizeData($request->getSnakeRequest());

        if (empty($data['title'])) {
            throw new \Exception('标题不能为空');
        }

        $todoItem->fill($data);
        $todoItem->edit();
        $todoItem->load('platform');

        return $this->resource($todoItem);
    }

    public function edit(TodoItem $todoItem, FormRequest $request)
    {
        $this->authorizeOwner($todoItem);
        $data = $this->normalizeData($request->getSnakeRequest());

        $todoItem->fill($data);
        $todoItem->edit();
        $todoItem->load('platform');

        return $this->resource($todoItem);
    }

    private function normalizeData(array $data): array
    {
        if (array_key_exists('platform_id', $data) && empty($data['platform_id'])) {
            $data['platform_id'] = 0;
        }
        return $data;
    }

    public function delete(TodoItem $todoItem)
    {
        $this->authorizeOwner($todoItem);
        $todoItem->delete();

        return response()->json([]);
    }

    public function updateStatus(TodoItem $todoItem, FormRequest $request)
    {
        $this->authorizeOwner($todoItem);

        $status = (int)$request->get('status');
        if ($status < 0 || $status > 3) {
            throw new \Exception('无效的状态值');
        }

        $todoItem->status = $status;
        $todoItem->edit();

        return $this->resource($todoItem);
    }

    public function statistics(FormRequest $request)
    {
        $query = TodoItem::query();
        foreach ($this->getAuthorizeConditions() as $condition) {
            $query->where($condition[0], $condition[1], $condition[2]);
        }

        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', 0)->count();
        $inProgress = (clone $query)->where('status', 1)->count();
        $completed = (clone $query)->where('status', 2)->count();

        return response()->json([
            'code' => 0,
            'msg' => '',
            'data' => compact('total', 'pending', 'inProgress', 'completed')
        ]);
    }

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
