<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\User\MemberRequest;
use App\Http\Resources\User\MemberResource;
use App\Models\User\Member;
use App\Services\Api\User\MemberService;

class MembersController extends Controller
{

    /**
     * 加载服务
     *
     * @param MemberService $memberService
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function __construct(private readonly MemberService $memberService)
    {
    }

    /**
     * 会员列表
     *
     * @param MemberRequest $request
     * @param Member $member
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function index(MemberRequest $request, Member $member)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($member->getRequestFilters());

        $config = [
            'includes' => ['user'],
            'allowedFilters' => $allowedFilters,
            'perPage' => $request->perPage(),
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
        $ip = $this->resolveClientIp($request);

        $responseData = [];

        try {
            $member = $this->memberService->getMember($data);

            switch ($type) {
                case 'wallpaper':
                    $responseData = $this->memberService->getWallpaperInfo($member, $ip);
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
        $ip = $this->resolveClientIp($request);

        $responseData = [];

        try {
            $member = auth()->user()->member;

            switch ($type) {
                case 'wallpaper':
                    $responseData = $this->memberService->getWallpaperInfo($member, $ip);
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
     * @param MemberRequest $request
     * @return MemberResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function status(Member $member, MemberRequest $request)
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
        $member = $this->memberService->add($data);

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

        $data = $this->memberService->completeMember($data);

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
        $member->admire = $request->get('admire');

        $member->edit();

        return new MemberResource($member);
    }

    /**
     * 解析客户端 IP。
     *
     * @param FormRequest $request
     * @return string|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function resolveClientIp(FormRequest $request): ?string
    {
        $ip = $request->getClientIp();
        $sourceIp = config('services.client_ip_override.source');
        $targetIp = config('services.client_ip_override.target');

        if ($sourceIp && $targetIp && $ip === $sourceIp) {
            return $targetIp;
        }

        return $ip;
    }
}
