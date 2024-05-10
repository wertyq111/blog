<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\FormRequests\Api\Web\LabelFormRequest;
use App\Http\Requests\Api\Web\LabelRequest;
use App\Http\Resources\Web\LabelResource;
use App\Models\Web\Label;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Requests\Api\FormRequest;

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
     * 标签列表
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 15:44
     */
    public function index(FormRequest $request, Label $label)
    {
        $config = [
            'includes' => ['articles', 'category']
        ];
        $labels = $this->queryBuilder($label, true, $config);

        return LabelResource::collection($labels);
    }

    /**
     * 标签所有列表
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 15:44
     */
    public function list(FormRequest $request, Label $label)
    {
        $config = [
            'includes' => ['category']
        ];
        $labels = $this->queryBuilder($label, false, $config);
        return $this->resource($labels, ['time' => true, 'collection' => true]);
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

        return $this->resource($label);

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

        return $this->resource($label);
    }

    /**
     * 删除标签
     *
     * @param Label $label
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/9 10:31
     */
    public function delete(Label $label)
    {
        $label->delete();

        return response()->json([]);
    }
}
