<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\MemberResource;
use App\Models\User\Member;

class MembersController extends Controller
{
    public function admires(Member $member)
    {
        $query = $member->query();
        $admires = $query->where('admire', '>', 0)->get();
        return new MemberResource($admires);
    }
}
