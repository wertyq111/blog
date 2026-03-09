<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkPlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkPlatformController extends Controller
{
    /**
     * 平台列表 - 分页
     *
     * @param FormRequest $request
     * @param WorkPlatform $workPlatform
     * @return \App\Http\Resources\BaseResource
     */
    public function index(FormRequest $request, WorkPlatform $workPlatform)
    {
        $allowedFilters = $request->generateAllowedFilters($workPlatform->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters,
            'orderBy' => [['sort' => 'asc'], ['id' => 'desc']]
        ];

        $workPlatforms = $this->queryBuilder($workPlatform, true, $config);

        return $this->resource($workPlatforms, ['time' => true, 'collection' => true]);
    }

    /**
     * 平台列表 - 不分页
     *
     * @param FormRequest $request
     * @param WorkPlatform $workPlatform
     * @return \App\Http\Resources\BaseResource
     */
    public function list(FormRequest $request, WorkPlatform $workPlatform)
    {
        $query = $workPlatform->newQuery();

        if ($request->get('status') !== null) {
            $query->where('status', $request->get('status'));
        }

        $workPlatforms = $query->orderBy('sort', 'asc')->orderBy('id', 'desc')->get();

        return $this->resource($workPlatforms);
    }

    /**
     * 平台详情
     *
     * @param WorkPlatform $workPlatform
     * @return \App\Http\Resources\BaseResource
     */
    public function info(WorkPlatform $workPlatform)
    {
        return $this->resource($workPlatform);
    }

    /**
     * 添加平台
     *
     * @param FormRequest $request
     * @param WorkPlatform $workPlatform
     * @return \App\Http\Resources\BaseResource
     */
    public function add(FormRequest $request, WorkPlatform $workPlatform)
    {
        $data = $request->getSnakeRequest();

        if (empty($data['name'])) {
            throw new \Exception('平台名称不能为空');
        }

        $workPlatform->fill($data);
        $workPlatform->edit();

        return $this->resource($workPlatform);
    }

    /**
     * 编辑平台
     *
     * @param WorkPlatform $workPlatform
     * @param FormRequest $request
     * @return \App\Http\Resources\BaseResource
     */
    public function edit(WorkPlatform $workPlatform, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        if (isset($data['name']) && empty($data['name'])) {
            throw new \Exception('平台名称不能为空');
        }

        $workPlatform->fill($data);
        $workPlatform->edit();

        return $this->resource($workPlatform);
    }

    /**
     * 删除平台
     *
     * @param WorkPlatform $workPlatform
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(WorkPlatform $workPlatform)
    {
        $workPlatform->delete();

        return response()->json([]);
    }

    /**
     * 批量保存排序
     */
    public function reorder(Request $request)
    {
        $order = $request->input('order', []);
        if (empty($order) || !is_array($order)) {
            return response()->json(['code' => 1, 'msg' => '参数错误']);
        }

        DB::beginTransaction();
        try {
            foreach ($order as $entry) {
                if (!isset($entry['id']) || !isset($entry['sort'])) {
                    continue;
                }
                WorkPlatform::where('id', $entry['id'])->update(['sort' => intval($entry['sort'])]);
            }
            DB::commit();
            return response()->json(['code' => 0, 'msg' => '排序保存成功']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('work-platform reorder error: ' . $e->getMessage());
            return response()->json(['code' => 2, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }
}
