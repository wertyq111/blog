<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\MemberRequest;
use App\Http\Resources\User\MemberResource;
use App\Models\User\Member;
use Illuminate\Http\Request;

class MembersController extends Controller
{
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
