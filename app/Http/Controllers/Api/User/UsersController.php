<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Models\User\Member;
use App\Models\User\User;
use App\Http\Controllers\Api\Controller;
use App\Http\Resources\User\UserResource;
use App\Http\Requests\Api\User\UserRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UsersController extends Controller
{
    /**
     * 获取用户列表
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/8 10:10
     */
    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::class)
            ->allowedIncludes('member', 'roles')
            ->allowedFilters([
                'username',
                'phone',
                AllowedFilter::exact('gender', 'member.gender'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('roles.id'),
            ])->paginate();

        $list = UserResource::collection($users);
        return $list;
    }

    /**
     * 获取用户详情
     *
     * @param User $user
     * @return UserResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/4/7 11:37
     */
    public function show(User $user)
    {
        $user->load(['member', 'roles']);

        return new UserResource($user);
    }

    /**
     * 修改状态
     *
     * @param UserRequest $request
     * @param User $user
     * @return UserResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/8 09:13
     */
    public function status(User $user, FormRequest $request)
    {
        $user->status = $request->get('status');
        $user->edit();

        return new UserResource($user);
    }

    /**
     * 创建用户
     *
     * @param UserRequest $request
     * @param User $user
     * @return UserResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 14:10
     */
    public function add(UserRequest $request, User $user)
    {
        $data = $request->getSnakeRequest();

        $user->fill($data);

        $user->edit();

        $user->roles()->sync($request->get('role_ids'),false);

        return new UserResource($user);

    }

    /**
     * 编辑用户
     *
     * @param User $user
     * @param UserRequest $request
     * @return UserResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/9 14:13
     */
    public function edit(User $user, UserRequest $request)
    {
        $data = $request->getSnakeRequest();
        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }
        $user->fill($data);

        // 清空老的所有角色
        $user->roles()->detach();
        // 同步新的所有角色
        $user->roles()->sync($request->get('role_ids'),false);

        $user->edit();

        return new UserResource($user);
    }

    /**
     * 删除用户
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/10 14:08
     */
    public function delete(User $user)
    {
        $user->delete();

        return response()->json([]);
    }

    /**
     * 重置密码
     *
     * @param UserRequest $request
     * @param User $user
     * @return UserResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/8 09:13
     */
    public function resetPwd(User $user)
    {
        $user->fill(['password' => '123456']);
        $user->edit();

        return new UserResource($user);
    }

    /**
     * 获取个人信息
     *
     * @param FormRequest $request
     * @return UserResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/8 11:43
     */
    public function getUserInfo(FormRequest $request)
    {
        return new UserResource($this->loadCurrentUserProfile());
    }

    /**
     * 更新当前用户资料
     *
     * @param FormRequest $request
     * @return UserResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/3/30 09:25
     */
    public function updateUserInfo(FormRequest $request)
    {
        $user = auth()->user();
        $data = $request->getSnakeRequest();

        $userPayload = [];
        if (array_key_exists('email', $data)) {
            $userPayload['email'] = $data['email'];
        }
        if (array_key_exists('mobile', $data)) {
            $userPayload['phone'] = $data['mobile'];
        }

        if ($userPayload) {
            $user->fill($userPayload);
            $user->edit();
        }

        $memberPayload = Arr::only($data, [
            'realname',
            'nickname',
            'gender',
            'avatar',
            'address',
            'intro',
        ]);

        $filledMemberPayload = array_filter($memberPayload, static function ($value) {
            return $value !== null && $value !== '';
        });

        if ($filledMemberPayload || $user->member) {
            $member = $user->member ?: new Member([
                'user_id' => $user->id,
                'avatar' => '',
            ]);
            if (array_key_exists('avatar', $memberPayload) && $memberPayload['avatar'] === null) {
                $memberPayload['avatar'] = $member->avatar ?? '';
            }
            $member->fill($memberPayload);
            $member->user_id = (string) $user->id;
            $member->edit();
        }

        return new UserResource($this->loadCurrentUserProfile());
    }

    /**
     * 加载当前用户及其资料
     *
     * @return User|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/3/30 09:25
     */
    protected function loadCurrentUserProfile()
    {
        $user = auth()->user();
        $user = QueryBuilder::for(User::class)->allowedIncludes('member')->where(['id' => $user->id])->first();
        if ($user && $user->member) {
            $user->member->avatar = $user->member->avatar
                ? $this->qiniuService->getPrivateUrl($user->member->avatar)
                : "";
        }

        return $user;
    }

    /**
     * 验证用户账号
     *
     * @param Request $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/8 16:06
     */
    public function checkUser(Request $request)
    {
        $username = $request->get('username');

        if(!$username) {
            throw new \Exception("缺少用户账号");
        }

        $user = QueryBuilder::for(User::class)->where(['username' => $username])->first();

        if($user) {
            throw new \Exception("用户已存在");
        } else {
            return new BaseResource([]);
        }
    }
}
