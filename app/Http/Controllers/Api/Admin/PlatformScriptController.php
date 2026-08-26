<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\PlatformScriptRequest;
use App\Http\Resources\BaseResource;
use App\Models\Admin\PlatformScriptRun;
use App\Services\Api\Admin\PlatformScript\PlatformScriptService;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

class PlatformScriptController extends Controller
{
    /**
     * 初始化平台脚本服务。
     *
     * @param PlatformScriptService $platformScriptService
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function __construct(private readonly PlatformScriptService $platformScriptService)
    {
        parent::__construct();
    }

    /**
     * 执行记录列表 - 分页
     *
     * @param PlatformScriptRequest $request
     * @param PlatformScriptRun $platformScriptRun
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function index(PlatformScriptRequest $request, PlatformScriptRun $platformScriptRun)
    {
        $allowedFilters = $request->generateAllowedFilters($platformScriptRun->getRequestFilters());

        $runs = QueryBuilder::for($platformScriptRun)
            ->allowedFilters($allowedFilters)
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return $this->resource($runs, ['time' => true, 'collection' => true]);
    }

    /**
     * 解析预览 / 查询：返回字段与将要使用的 ordrNo 或查询结果，不执行修改。
     *
     * @param PlatformScriptRequest $request
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    public function preview(PlatformScriptRequest $request): JsonResponse
    {
        return response()->json($this->platformScriptService->preview(
            $request->validated('script_key'),
            $request->validated(),
        ));
    }

    /**
     * 执行操作：解析/执行、自增 ordrNo、远端执行并落库。
     *
     * @param PlatformScriptRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    public function run(PlatformScriptRequest $request)
    {
        $run = $this->platformScriptService->run(
            $request->validated('script_key'),
            $request->validated(),
        );

        return $this->resource($run);
    }


    /**
     * 执行记录详情
     *
     * @param PlatformScriptRun $platformScriptRun
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function info(PlatformScriptRun $platformScriptRun)
    {
        return $this->resource($platformScriptRun);
    }
}
