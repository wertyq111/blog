<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkPlatform;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class WorkPlatformController extends Controller
{
    public function index(FormRequest $request, WorkPlatform $workPlatform)
    {
        $allowedFilters = $request->generateAllowedFilters($workPlatform->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters,
            'orderBy' => [[
                'sort' => 'asc'
            ], [
                'id' => 'desc'
            ]],
            'perPage' => $request->input('limit', 10)
        ];

        $platforms = $this->queryBuilder($workPlatform, true, $config);

        return $this->resource($platforms, ['time' => true, 'collection' => true]);
    }

    // ... other existing methods ...

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
                if (!isset($entry['id']) || !isset($entry['sort'])) continue;
                WorkPlatform::where('id', $entry['id'])->update(['sort' => intval($entry['sort'])]);
            }
            DB::commit();
            return response()->json(['code' => 0, 'msg' => '排序保存成功']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('work-platform reorder error: '.$e->getMessage());
            return response()->json(['code' => 2, 'msg' => '保存失败: '.$e->getMessage()]);
        }
    }
}
