<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Web\LabelRequest;
use App\Http\Resources\Web\LabelResource;
use App\Models\Web\Label;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;

/**
 * 标签
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2023/6/12
 * Class LabelsController
 * @package App\Http\Controllers\Api\Web
 */
class LabelsController extends Controller
{
    /**
     * 标签列表(后台)
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 15:44
     */
    public function index(Request $request)
    {
        $labels = QueryBuilder::for(Label::class)
            ->allowedFields('category_id')
            ->paginate();
        return LabelResource::collection($labels);
    }

    /**
     * 标签列表(前台)
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 15:44
     */
    public function list(Request $request)
    {
        $labels = QueryBuilder::for(Label::class)
            ->allowedFields('category_id')
            ->paginate();
        return LabelResource::collection($labels);
    }

    /**
     * 添加标签
     *
     * @param LabelRequest $request
     * @param Label $label
     * @return LabelResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 09:28
     */
    public function add(LabelRequest $request, Label $label)
    {
        $data = $request->getSnakeRequest();

        $label->fill($data);
        $label->category_id = $data['category_id'];

        $label->edit();

        return new LabelResource($label);

    }

    /**
     * 编辑标签
     *
     * @param Label $label
     * @param LabelRequest $request
     * @return LabelResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 09:28
     */
    public function edit(Label $label, LabelRequest $request)
    {
        $label->fill($request->getSnakeRequest());

        $label->edit();

        return new LabelResource($label);
    }

    /**
     * 删除标签
     *
     * @param Label $label
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 09:27
     */
    public function delete(Label $label)
    {
        $label->delete();

        return response(null, 204);
    }
}
