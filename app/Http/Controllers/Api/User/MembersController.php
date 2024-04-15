<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\User\MemberRequest;
use App\Http\Resources\User\MemberResource;
use App\Models\User\Member;
use App\Services\Api\User\MemberService;
use Illuminate\Http\Request;

class MembersController extends Controller
{

    /**
     * 加载服务
     */
    public function __construct()
    {
        $this->service = new MemberService();
    }

    /**
     * 会员列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 09:33
     */
    public function index(FormRequest $request, Member $member)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($member->getRequestFilters());

        $config = [
            'includes' => ['user'],
            'allowedFilters' => $allowedFilters
        ];
        $members = $this->queryBuilder($member, true, $config);

        $list = MemberResource::collection($members);
        return $list;
    }

    /**
     * 获取会员信息
     *
     * @param FormRequest $request
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:16
     */
    public function info(FormRequest $request)
    {
        $data = $request->getSnakeRequest();
        $type = $data['type'] ?? null;
        $ip = $request->getClientIp();
        // 测试 ip
        if ($ip == '192.168.28.59') {
            $ip = '125.118.5.27';
        }

        $responseData = [];

        try {
            $member = $this->service->getMember($data);

            switch ($type) {
                case 'wallpaper':
                    $responseData = $this->service->getWallpaperInfo($member, $ip);
                    break;
            }
        } catch (\Exception $e) {
            throw $e;
        }


        return $this->resource($responseData);
    }

    /**
     * 获取当前会员信息
     *
     * @param FormRequest $request
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:16
     */
    public function user(FormRequest $request)
    {
        $data = $request->getSnakeRequest();
        $type = $data['type'] ?? null;
        $ip = $request->getClientIp();
        // 测试 ip
        if ($ip == '192.168.28.59') {
            $ip = '125.118.5.27';
        }

        $responseData = [];

        try {
            $member = auth()->user()->member;

            switch ($type) {
                case 'wallpaper':
                    $responseData = $this->service->getWallpaperInfo($member, $ip);
                    break;
            }
        } catch (\Exception $e) {
            throw $e;
        }


        return $this->resource($responseData);
    }

    /**
     * 修改状态
     *
     * @param Member $member
     * @param FormRequest $request
     * @return MemberResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 09:37
     */
    public function status(Member $member, FormRequest $request)
    {
        $member->status = $request->get('status');
        $member->edit();

        return new MemberResource($member);
    }

    /**
     * 创建会员
     *
     * @param MemberRequest $request
     * @return MemberResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 09:39
     */
    public function add(MemberRequest $request)
    {
        $data = $request->getSnakeRequest();

        // 添加会员
        $member = $this->service->add($data);

        return new MemberResource($member);

    }

    /**
     * 修改会员
     *
     * @param Member $member
     * @param MemberRequest $request
     * @return MemberResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 09:39
     */
    public function edit(Member $member, MemberRequest $request)
    {
        $data = $request->getSnakeRequest();

        $data = $this->service->completeMember($data);

        $member->fill($data);

        $member->edit();

        return new MemberResource($member);
    }

    /**
     * 删除会员
     *
     * @param Member $member
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 10:13
     */
    public function delete(Member $member)
    {
        $member->delete();

        return response()->json([]);
    }

    /**
     * 更新打赏
     *
     * @param MemberRequest $request
     * @param Member $member
     * @return MemberResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/8 16:54
     */
    public function updateAdmire(MemberRequest $request, Member $member)
    {
        $member = $member->find($request->get('id'));

        $member->admire = $request->get('admire');

        $member->edit();

        return new MemberResource($member);
    }
}
