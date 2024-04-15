<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Models\User\MemberLevel;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class MemberLevelController extends Controller
{
    /**
     * 会员等级列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 09:33
     */
    public function index(FormRequest $request, MemberLevel $memberLevel)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($memberLevel->getRequestFilters());

        $memberLevels = QueryBuilder::for($memberLevel)
            ->allowedFilters($allowedFilters)->paginate();

        return $this->resource($memberLevels, ['time' => true, 'collection' => true]);
    }

    /**
     * 会员等级列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 09:33
     */
    public function list(FormRequest $request, MemberLevel $memberLevel)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($memberLevel->getRequestFilters());

        $memberLevels = QueryBuilder::for($memberLevel)
            ->allowedFilters($allowedFilters)->get();

        return $this->resource($memberLevels, ['time' => true, 'collection' => true]);
    }

    /**
     * 修改状态
     *
     * @param MemberLevel $memberLevel
     * @param FormRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 09:37
     */
    public function status(MemberLevel $memberLevel, FormRequest $request)
    {
        $memberLevel->status = $request->get('status');
        $memberLevel->edit();

        return $this->resource($memberLevel);
    }

    /**
     * 创建会员等级
     *
     * @param FormRequest $request
     * @param MemberLevel $memberLevel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 10:52
     */
    public function add(FormRequest $request, MemberLevel $memberLevel)
    {
        $data = $request->all();

        $memberLevel->fill($data);

        $memberLevel->edit();

        return $this->resource($memberLevel);

    }

    /**
     * 修改会员等级
     *
     * @param MemberLevel $memberLevel
     * @param FormRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 09:39
     */
    public function edit(MemberLevel $memberLevel, FormRequest $request)
    {
        $data = $request->all();

        $memberLevel->fill($data);

        $memberLevel->edit();

        return $this->resource($memberLevel);
    }

    /**
     * 删除会员等级
     *
     * @param MemberLevel $memberLevel
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 10:13
     */
    public function delete(MemberLevel $memberLevel)
    {
        $memberLevel->delete();

        return response()->json([]);
    }

    /**
     * 批量删除
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:32
     */
    public function batchDelete(FormRequest $request, MemberLevel $memberLevel)
    {
        $ids = $request->get('id');
        foreach($ids as $id) {
            $this->delete($memberLevel->find($id));
        }

        return response()->json([]);
    }
}
