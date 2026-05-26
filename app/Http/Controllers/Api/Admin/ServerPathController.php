<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\ServerPathRequest;
use App\Http\Resources\BaseResource;
use App\Models\Admin\ServerPath;
use App\Services\Api\Admin\ServerPathService;
use GuzzleHttp\Utils;
use Spatie\QueryBuilder\QueryBuilder;

class ServerPathController extends Controller
{
    /**
     * 初始化服务器路径服务。
     *
     * @param ServerPathService $serverPathService
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function __construct(private readonly ServerPathService $serverPathService)
    {
        parent::__construct();
    }


    /**
     * 服务器路径列表 - 分页
     *
     * @param ServerPathRequest $request
     * @param ServerPath $serverPath
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function index(ServerPathRequest $request, ServerPath $serverPath)
    {
        $allowedFilters = $request->generateAllowedFilters($serverPath->getRequestFilters());

        $serverPaths = QueryBuilder::for($serverPath)
            ->allowedFilters($allowedFilters)
            ->orderBy('sort', 'asc')
            ->paginate($request->perPage());

        return $this->resource($serverPaths, ['time' => true, 'collection' => true]);
    }

    /**
     * 服务器路径详情
     *
     * @param ServerPath $serverPath
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 13:28
     */
    public function info(ServerPath $serverPath)
    {
        return $this->resource($serverPath);
    }

    /**
     * 添加服务器路径
     *
     * @param ServerPathRequest $request
     * @param ServerPath $serverPath
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function add(ServerPathRequest $request, ServerPath $serverPath)
    {
        $data = $this->normalizePayload($request);

        $serverPath->fill($data);

        $serverPath->edit();

        return $this->resource($serverPath);
    }

    /**
     * 编辑服务器路径
     *
     * @param ServerPath $serverPath
     * @param ServerPathRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function edit(ServerPath $serverPath, ServerPathRequest $request)
    {
        $data = $this->normalizePayload($request);

        $serverPath->fill($data);

        $serverPath->edit();

        return $this->resource($serverPath);
    }

    /**
     * 服务器路径转换
     *
     * @param ServerPath $serverPath
     * @param ServerPathRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function convert(ServerPath $serverPath, ServerPathRequest $request)
    {
        $data = $request->validated();

        $serverPaths = $this->serverPathService->convert($serverPath, $data['paths']);

        return response()->json($serverPaths);
    }

    /**
     * 删除服务器路径
     *
     * @param ServerPath $serverPath
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:23
     */
    public function delete(ServerPath $serverPath)
    {
        $serverPath->delete();

        return response()->json([]);
    }

    /**
     * 兼容旧版前端批量删除
     *
     * @param ServerPathRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function batchDelete(ServerPathRequest $request)
    {
        ServerPath::query()->whereIn('id', $request->integerIds())->delete();

        return response()->json([]);
    }

    /**
     * 归一化服务器路径保存数据。
     *
     * @param ServerPathRequest $request
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function normalizePayload(ServerPathRequest $request): array
    {
        $data = $request->getSnakeRequest();
        $data['sort'] = (int) $data['sort'];
        $data['sources'] = Utils::jsonEncode($data['sources']);

        return $data;
    }

}
