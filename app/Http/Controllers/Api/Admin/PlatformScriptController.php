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
     * 执行记录列表 - 按流水号分组分页
     *
     * @param PlatformScriptRequest $request
     * @param PlatformScriptRun $platformScriptRun
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/31
     */
    public function index(PlatformScriptRequest $request, PlatformScriptRun $platformScriptRun)
    {
        $allowedFilters = $request->generateAllowedFilters($platformScriptRun->getRequestFilters());

        // 分页单位是流水号：先按流水号分组分页，保证同一流水号的多条记录不被切开
        $ordrNoPage = QueryBuilder::for($platformScriptRun)
            ->allowedFilters($allowedFilters)
            ->selectRaw('ordr_no, MAX(id) AS last_id')
            ->groupBy('ordr_no')
            ->orderByDesc('last_id')
            ->paginate($request->perPage());

        $runs = PlatformScriptRun::query()
            ->whereIn('ordr_no', collect($ordrNoPage->items())->pluck('ordr_no')->all())
            ->orderByDesc('id')
            ->get();

        $ordrNoPage->setCollection($runs);

        return $this->resource($ordrNoPage, ['time' => true, 'collection' => true]);
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
     * 查询授信进度：只读同步交行侧状态，不执行推送。
     *
     * @param PlatformScriptRequest $request
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/31
     */
    public function progress(PlatformScriptRequest $request): JsonResponse
    {
        return response()->json($this->platformScriptService->progress(
            $request->validated('script_key'),
            $request->validated(),
        ));
    }

    /**
     * 推送确认担保：向银行发送同意担保报文并落库存档。
     *
     * @param PlatformScriptRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/31
     */
    public function confirmGuarantee(PlatformScriptRequest $request)
    {
        $run = $this->platformScriptService->confirmGuarantee(
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
