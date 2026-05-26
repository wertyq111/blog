<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\WorkDocRequest;
use App\Models\Admin\WorkDoc;
use Spatie\QueryBuilder\QueryBuilder;

class WorkDocController extends Controller
{
    /**
     * 牛马文档列表 - 分页
     *
     * @param WorkDocRequest $request
     * @param WorkDoc $workDoc
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function index(WorkDocRequest $request, WorkDoc $workDoc)
    {
        $allowedFilters = $request->generateAllowedFilters($workDoc->getRequestFilters());

        $keyword = $request->get('keyword') ?: ($request->get('content') ?: $request->get('title'));

        $query = QueryBuilder::for($workDoc->newQuery()->with('category'))
            ->allowedFilters($allowedFilters);

        if (!empty($keyword)) {
            $query->whereRaw('MATCH(title, content) AGAINST (? IN BOOLEAN MODE)', [$keyword]);
        }

        $query->orderBy('is_pin', 'desc')
            ->orderBy('priority', 'desc')
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc');

        $docs = $query->paginate($request->perPage());

        return $this->resource($docs, ['time' => true, 'collection' => true]);
    }

    /**
     * 文档详情
     *
     * @param WorkDoc $workDoc
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function info(WorkDoc $workDoc)
    {
        $workDoc->load('category');

        return $this->resource($workDoc, ['time' => true]);
    }

    /**
     * 添加文档
     *
     * @param WorkDocRequest $request
     * @param WorkDoc $workDoc
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function add(WorkDocRequest $request, WorkDoc $workDoc)
    {
        $data = $request->getSnakeRequest();

        $workDoc->fill($data);
        $workDoc->edit();

        $workDoc->load('category');

        return $this->resource($workDoc);
    }

    /**
     * 编辑文档
     *
     * @param WorkDoc $workDoc
     * @param WorkDocRequest $request
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function edit(WorkDoc $workDoc, WorkDocRequest $request)
    {
        $data = $request->getSnakeRequest();

        $workDoc->fill($data);
        $workDoc->edit();

        $workDoc->load('category');

        return $this->resource($workDoc);
    }

    /**
     * 删除文档
     *
     * @param WorkDoc $workDoc
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function delete(WorkDoc $workDoc)
    {
        $workDoc->delete();

        return response()->json([]);
    }
}
