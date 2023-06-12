<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\MemberResource;
use App\Models\User\Member;

class MembersController extends Controller
{
    /**
     * 会员打赏列表(不分页)
     *
     * @param Member $member
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 10:13
     */
    public function admires(Member $member)
    {
        $query = $member->query();
        $admires = $query->select('nickname', 'admire')->where('admire', '>', 0)->get();
        return (new MemberResource($admires))->response()->setStatusCode(200);
    }
}
