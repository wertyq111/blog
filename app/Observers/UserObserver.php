<?php

namespace App\Observers;
use App\Models\User\User;

/**
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2024/3/10
 * Class UserObserver
 * @package App\Observers
 */
class UserObserver
{
    /**
     * 删除对应角色的关系中间表记录
     *
     * @param User $user
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 16:26
     */
    public function deleting(User $user)
    {
        $user->roles()->detach();
    }
}
