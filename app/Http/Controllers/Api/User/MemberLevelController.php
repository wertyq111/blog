<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\MemberLevelRequest;
use App\Http\Resources\BaseResource;
use App\Models\User\MemberLevel;

class MemberLevelController extends Controller
{
    /**
     * 会员等级列表
     *
     * @param MemberLevelRequest $request
     * @param MemberLevel $memberLevel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function index(MemberLevelRequest $request, MemberLevel $memberLevel)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($memberLevel->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters,
            'perPage' => $request->perPage(),
        ];
        $memberLevels = $this->queryBuilder($memberLevel, true, $config);

        return $this->resource($memberLevels, ['time' => true, 'collection' => true]);
    }

    /**
     * 会员等级列表
     *
     * @param MemberLevelRequest $request
     * @param MemberLevel $memberLevel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function list(MemberLevelRequest $request, MemberLevel $memberLevel)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($memberLevel->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters,
        ];
        $memberLevels = $this->queryBuilder($memberLevel, false, $config);

        return $this->resource($memberLevels, ['time' => true, 'collection' => true]);
    }

    /**
     * 修改状态
     *
     * @param MemberLevel $memberLevel
     * @param MemberLevelRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function status(MemberLevel $memberLevel, MemberLevelRequest $request)
    {
        $memberLevel->status = $request->get('status');
        $memberLevel->edit();

        return $this->resource($memberLevel);
    }

    /**
     * 创建会员等级
     *
     * @param MemberLevelRequest $request
     * @param MemberLevel $memberLevel
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function add(MemberLevelRequest $request, MemberLevel $memberLevel)
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
     * @param MemberLevelRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function edit(MemberLevel $memberLevel, MemberLevelRequest $request)
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
     * @param MemberLevelRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function batchDelete(MemberLevelRequest $request, MemberLevel $memberLevel)
    {
        foreach($request->integerIds() as $id) {
            $this->delete($memberLevel->find($id));
        }

        return response()->json([]);
    }
}
