<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkDoc;
use Spatie\QueryBuilder\QueryBuilder;

class WorkDocController extends Controller
{
    /**
     * 牛马文档列表 - 分页
     */
    public function index(FormRequest $request, WorkDoc $workDoc)
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

        $docs = $query->paginate($request->get('pageSize') ?? self::PER_PAGE);

        return $this->resource($docs, ['time' => true, 'collection' => true]);
    }

    /**
     * 文档详情
     */
    public function info(WorkDoc $workDoc)
    {
        $workDoc->load('category');

        return $this->resource($workDoc, ['time' => true]);
    }

    /**
     * 添加文档
     */
    public function add(FormRequest $request, WorkDoc $workDoc)
    {
        $data = $request->getSnakeRequest();

        $this->validateDoc($data);

        $workDoc->fill($data);
        $workDoc->edit();

        $workDoc->load('category');

        return $this->resource($workDoc);
    }

    /**
     * 编辑文档
     */
    public function edit(WorkDoc $workDoc, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        $this->validateDoc($data, false);

        $workDoc->fill($data);
        $workDoc->edit();

        $workDoc->load('category');

        return $this->resource($workDoc);
    }

    /**
     * 删除文档
     */
    public function delete(WorkDoc $workDoc)
    {
        $workDoc->delete();

        return response()->json([]);
    }

    private function validateDoc(array $data, bool $isCreate = true)
    {
        if ($isCreate && empty($data['category_id'])) {
            throw new \Exception('请选择分类');
        }
        if (isset($data['title']) && empty($data['title'])) {
            throw new \Exception('标题不能为空');
        }
        if (isset($data['content']) && empty($data['content'])) {
            throw new \Exception('内容不能为空');
        }
    }

}

