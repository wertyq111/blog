<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\WorkPlatformRequest;
use App\Models\Admin\WorkPlatform;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class WorkPlatformController extends Controller
{
    /**
     * 平台列表 - 分页
     *
     * @param WorkPlatformRequest $request
     * @param WorkPlatform $workPlatform
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function index(WorkPlatformRequest $request, WorkPlatform $workPlatform)
    {
        $allowedFilters = $request->generateAllowedFilters($workPlatform->getRequestFilters());

        $query = QueryBuilder::for($workPlatform->newQuery())
            ->allowedFilters($allowedFilters);

        $this->applyOwnerFilter($query);

        $workPlatforms = $query
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($request->perPage());

        return $this->resource($workPlatforms, ['time' => true, 'collection' => true]);
    }

    /**
     * 平台列表 - 不分页
     *
     * @param WorkPlatformRequest $request
     * @param WorkPlatform $workPlatform
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function list(WorkPlatformRequest $request, WorkPlatform $workPlatform)
    {
        $query = $workPlatform->newQuery();

        if ($request->get('status') !== null) {
            $query->where('status', $request->get('status'));
        }

        $this->applyOwnerFilter($query);

        $workPlatforms = $query->orderBy('sort', 'asc')->orderBy('id', 'desc')->get();

        return $this->resource($workPlatforms);
    }

    /**
     * 平台详情
     *
     * @param WorkPlatform $workPlatform
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function info(WorkPlatform $workPlatform)
    {
        return $this->resource($workPlatform);
    }

    /**
     * 添加平台
     *
     * @param WorkPlatformRequest $request
     * @param WorkPlatform $workPlatform
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function add(WorkPlatformRequest $request, WorkPlatform $workPlatform)
    {
        $data = $request->getSnakeRequest();

        $workPlatform->fill($data);
        $workPlatform->edit();

        return $this->resource($workPlatform);
    }

    /**
     * 编辑平台
     *
     * @param WorkPlatform $workPlatform
     * @param WorkPlatformRequest $request
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function edit(WorkPlatform $workPlatform, WorkPlatformRequest $request)
    {
        $data = $request->getSnakeRequest();

        $workPlatform->fill($data);
        $workPlatform->edit();

        return $this->resource($workPlatform);
    }

    /**
     * 删除平台
     *
     * @param WorkPlatform $workPlatform
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function delete(WorkPlatform $workPlatform)
    {
        $workPlatform->delete();

        return response()->json([]);
    }

    /**
     * 批量保存排序
     *
     * @param WorkPlatformRequest $request
     * @param WorkPlatform $workPlatform
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function reorder(WorkPlatformRequest $request, WorkPlatform $workPlatform)
    {
        $order = $request->input('order', $request->input('list', []));

        DB::transaction(function () use ($order, $workPlatform) {
            foreach ($order as $item) {
                $workPlatform->newQuery()->where('id', $item['id'])->update([
                    'sort' => (int)$item['sort']
                ]);
            }
        });

        return response()->json(['code' => 0, 'msg' => '排序已保存']);
    }

    /**
     * 应用当前用户平台数据范围。
     *
     * @param mixed $query
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function applyOwnerFilter($query): void
    {
        $user = auth('api')->user();
        if (!$user) {
            return;
        }

        $isSuper = false;
        foreach ($user->roles as $role) {
            if (($role->code ?? null) === 'super') {
                $isSuper = true;
                break;
            }
        }

        if (!$isSuper) {
            $query->where('create_user', $user->id);
        }
    }
}
