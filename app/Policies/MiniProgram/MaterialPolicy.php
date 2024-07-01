<?php

namespace App\Policies\MiniProgram;

use App\Models\MiniProgram\Material;
use App\Models\User\User;
use App\Policies\BasePolicy;
use App\Services\Api\MiniProgram\MaterialService;

class MaterialPolicy extends BasePolicy
{
    protected $materialService = null;

    public function __construct()
    {
        parent::__construct();

        $this->materialService = new MaterialService();
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
        $sharedMembers = $this->materialService->getSharedMembers($currentUser);
        return $sharedMembers && in_array($model->member_id, $sharedMembers) ?: $currentUser->isAuthorOf($model);
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
        $sharedMembers = $this->materialService->getSharedMembers($currentUser);
        return $sharedMembers && in_array($model->member_id, $sharedMembers) ?: $currentUser->isAuthorOf($model);
    }
}
