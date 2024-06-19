<?php

namespace App\Policies;

use App\Models\User\User;

class BasePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * 更新
     *
     * @param User $currentUser
     * @param $model
     * @return bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 15:41
     */
    public function update(User $currentUser, $model)
    {
        return $currentUser->isAuthorOf($model);
    }

    /**
     * @param User $currentUser
     * @param $model
     * @return bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 15:51
     */
    public function delete(User $currentUser, $model)
    {
        return $currentUser->isAuthorOf($model);
    }
}
