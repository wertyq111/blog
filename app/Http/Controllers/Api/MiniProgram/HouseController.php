<?php

namespace App\Http\Controllers\Api\MiniProgram;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\MiniProgram\HouseRequest;
use App\Http\Resources\BaseResource;
use App\Models\MiniProgram\House;
use App\Services\Api\MiniProgram\HouseService;

class HouseController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new HouseService();
    }


    /**
     * 列表
     *
     * @param FormRequest $request
     * @param House $house
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 14:33
     */
    public function index(FormRequest $request, House $house)
    {
        $data = $request->all();

        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($house->getRequestFilters());

        // 共享表
        $sharedMembers = $this->service->getSharedMembers();

        if($sharedMembers) {
            $currentMember = $this->authorizeForMember();
            $conditions = ['in' => ['member_id', 'in', array_merge($sharedMembers, [$currentMember['member_id']])]];
        } else {
            $conditions = $this->authorizeForMember();
        }
//        dd($conditions);

        $config = [
            'includes' => ['member', 'parents', 'children', 'materials'],
            'allowedFilters' => $allowedFilters,
            'perPage' => $data['perPage'] ?? null,
            'orderBy' => $data['orderBy'] ?? null,
            'conditions' => $conditions
        ];
        $houses = $this->queryBuilder($house, true, $config);

        return $this->resource($houses, ['time' => true, 'collection' => true]);
    }

    /**
     * 所有列表
     *
     * @param FormRequest $request
     * @param House $house
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/18 13:38
     */
    public function list(FormRequest $request, House $house)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($house->getRequestFilters());

        // 共享表
        $sharedMembers = $this->service->getSharedMembers();

        if($sharedMembers) {
            $currentMember = $this->authorizeForMember();
            $conditions = ['in' => ['member_id', 'in', array_merge($sharedMembers, [$currentMember['member_id']])]];
        } else {
            $conditions = $this->authorizeForMember();
        }

        $config = [
            'includes' => ['member', 'parent', 'children', 'materials'],
            'allowedFilters' => $allowedFilters,
            'conditions' => $conditions
        ];
        $houses = $this->queryBuilder($house, false, $config);

        return $this->resource($houses, ['time' => true, 'collection' => true]);
    }

    /**
     * 详情
     *
     * @param House $house
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 15:13
     */
    public function info(House $house)
    {
        $this->authorize('update', $house);

        return $this->resource($house);
    }

    /**
     * 添加
     *
     * @param HouseRequest $request
     * @param House $house
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 14:39
     */
    public function add(HouseRequest $request, House $house)
    {
        $data = $request->getSnakeRequest();

        $house = $this->service->add($house, $data);

        return $this->resource($house);
    }

    /**
     * 编辑
     *
     * @param House $house
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/30 15:14
     */
    public function edit(House $house, FormRequest $request)
    {
        $this->authorize('update', $house);

        $data = $request->getSnakeRequest();

        if(isset($data['url'])) {
            $imagePath = $house->url != $data['url']
                ? str_replace("http://" . env("QINIU_DOMAIN", null) . "/", "", $house->url)
                : null;
        }

        $house->fill($data);

        $house->edit();

        if(isset($data['url'])) {
            // 删除原先的图片
            if ($imagePath !== null) {
                $this->qiniuService->delete($imagePath);
            }
        }

        return $this->resource($house);
    }

    /**
     * 删除
     *
     * @param House $house
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 15:57
     */
    public function delete(House $house)
    {
        $this->authorize('delete', $house);

        // 如果有图片就删除图片
        if($house->pic_url) {
            $imagePath = preg_replace("/http(s):\/\/" . env("QINIU_DOMAIN", null) . "\//", "", $house->pic_url);

            // 删除原先的图片
            if ($imagePath !== null) {
                $this->qiniuService->delete($imagePath);
            }
        }

        $house->delete();

        return response()->json([]);
    }

    /**
     * 批量删除
     *
     * @param FormRequest $request
     * @param House $house
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/12 14:02
     */
    public function batchDelete(FormRequest $request, House $house)
    {
        $ids = $request->get('id');
        foreach($ids as $id) {
            $this->delete($house->find($id));
        }

        return response()->json([]);
    }

    /**
     * 校验
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/19 13:50
     */
    public function check(FormRequest $request)
    {
        $name = $request->get('name');
        $pid = $request->get('pid') ?: 0;

        $house = $this->memberExistCheck(House::class, ['name' => $name, 'pid' => $pid]);

        return $house && $name
            ? $this->resource($house, ['time' => true, 'collection' => true])
            : response()->json([]);
    }
}
