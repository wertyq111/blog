<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Requests\Api\User\AuthorizationRequest;
use App\Http\Resources\BaseResource;
use App\Models\User\User;
use App\Http\Controllers\Api\Controller;
use App\Http\Resources\User\UserResource;
use App\Http\Requests\Api\User\UserRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Propaganistas\LaravelPhone\PhoneNumber;
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
                AllowedFilter::exact('status'),
                AllowedFilter::exact('roles.id'),
            ])->paginate();

        return UserResource::collection($users);
    }

    public function status(UserRequest $request, User $user)
    {
        $user = $user->find($request->get('id'));
        $user->status = $request->get('status');
        $user->edit();

        return new UserResource($user);
    }

    /**
     * 注册账号
     *
     * @param UserRequest $request
     * @return UserResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/1 14:05
     */
    public function register(UserRequest $request)
    {
        $cacheKey = 'verificationCode_' . $request->verification_key;
        $verifyData = \Cache::get($cacheKey);

        if (!$verifyData) {
            abort(403, '验证码已失效');
        }

        if (!hash_equals($verifyData['code'], $request->verification_code)) {
            // 返回401
            throw new AuthenticationException('验证码错误');
        }

        $user = User::create([
            'username' => $request->get('username'),
            'phone' => $verifyData['phone'],
            'password' => $request->get('password'),
            'status' => true
        ]);

        // 清除验证码缓存
        \Cache::forget($cacheKey);

        return new UserResource($user);
    }

    /**
     * 用户登录
     *
     * @param AuthorizationRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/14 14:43
     */
    public function login(AuthorizationRequest $request)
    {
        $username = $request->get('username');
        $phoneValid = new PhoneNumber($username, 'CN');
        $credentials = [];

        $phoneValid->isValid() ? $credentials['phone'] = $username :
            (filter_var($username, FILTER_VALIDATE_EMAIL) ? $credentials['email'] = $username :
                $credentials['username'] = $username);

        $credentials['password'] = $request->get('password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            throw new AuthenticationException('用户名或密码错误');
        }

        $data = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'member' => auth('api')->user()->member
        ];


        return new BaseResource($data);

    }
}
