<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\EasywechatAuthorizationRequest;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\User\AuthorizationRequest;
use App\Http\Requests\Api\WebAuthorizationRequest;
use App\Models\User\Member;
use App\Models\User\User;
use Illuminate\Auth\AuthenticationException;
use App\Http\Requests\Api\SocialAuthorizationRequest;
use Illuminate\Support\Facades\Auth;
use Overtrue\LaravelSocialite\Socialite;
use Overtrue\LaravelWeChat\EasyWeChat;
use Propaganistas\LaravelPhone\PhoneNumber;

class AuthorizationsController extends Controller
{
    /**
     * 默认密码
     */
    const DEFAULT_PASSWORD = '123456';

    /**
     * 默认用户组: 普通会员
     */
    const DEFAULT_ROLES = [5];

    /**
     * 用户组账号前缀
     */
    const ACCOUNT_GROUP = [
        'mini_program' => 'mp',
        'wechat' => 'we'
    ];

    /**
     * 默认会员信息
     */
    const DEFAULT_MEMBER = [
        'member_level' => 1, // 青铜
        'status' => 1, //激活
    ];

    /**
     * @param $type
     * @param SocialAuthorizationRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/19 14:09
     */
    public function socialStore($type, SocialAuthorizationRequest $request)
    {
        $driver = Socialite::create($type);

        try {
            if ($code = $request->code) {
                $oauthUser = $driver->userFromCode($code);
            } else {
                // 微信需要增加 openid
                if ($type == 'wechat') {
                    $driver->withOpenid($request->openid);
                }

                $oauthUser = $driver->userFromToken($request->access_token);
            }
        } catch (\Exception $e) {
            throw new AuthenticationException('参数错误，未获取用户信息');
        }

        if (!$oauthUser->getId()) {
            throw new AuthenticationException('参数错误，未获取用户信息');
        }

        switch ($type) {
            case 'wechat':
                $unionid = $oauthUser->getRaw()['unionid'] ?? null;

                if ($unionid) {
                    $user = User::where('unionid', $unionid)->first();
                } else {
                    $user = User::where('openid', $oauthUser->getId())->first();
                }

                // 没有用户，默认创建一个用户
                if (!$user) {
                    $user = new User();
                    $data = [
                        'username' => $oauthUser->getNickname(),
                        'openid' => $oauthUser->getId(),
                        'unionid' => $unionid,
                    ];
                    $user->fill($data);

                    $user->edit();
                }

                break;
        }

        $token = auth('api')->login($user);

        return $this->respondWithToken($user, $token);
    }

    /**
     * 微信小程序登录
     *
     * @param $type
     * @param EasywechatAuthorizationRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/25 09:37
     */
    public function easywechatStore($type, EasywechatAuthorizationRequest $request)
    {
        $miniProgram = EasyWeChat::miniApp();

        try {
            if ($code = $request->code) {
                $utils = $miniProgram->getUtils();
                $oauthUser = $utils->codeToSession($code);
            }
        } catch (\Exception $e) {
            throw new AuthenticationException('参数错误，未获取用户信息');
        }

        try {
            // 找到 openid 对应的用户
            $user = User::where('openid', $oauthUser['openid'])->first();

            $attributes['weixin_session_key'] = $oauthUser['session_key'];


            if (!$user) {
                //添加用户信息
                $user = new User();
                $data = [
                    'openid' => $oauthUser['openid'],
                    'weixin_session_key' => $oauthUser['session_key'],
                ];
                $user->fill($data);
                $user->edit();

                if ($user->id) {
                    // 添加用户账号及密码
                    $user->fill([
                        'username' => self::ACCOUNT_GROUP['mini_program'] . str_pad($user->id, 6, 0, STR_PAD_LEFT),
                        'password' => self::DEFAULT_PASSWORD
                    ]);

                    // 更新用户组
                    $user->roles()->sync(self::DEFAULT_ROLES, false);

                    $user->edit();

                    // 创建用户会员记录
                    $member = new Member();
                    $memberInfo = array_merge([
                        'user_id' => $user->id,
                        'nickname' => "微信小程序用户"
                    ], self::DEFAULT_MEMBER);
                    $member->fill($memberInfo);
                    $member->edit();
                }
            } else {
                // 更新用户数据
                $user->fill($attributes);
                $user->edit();
            }

            // 为对应用户创建 JWT
            $token = auth('api')->login($user);

            return $this->respondWithToken($user, $token);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 注册账号
     *
     * @param WebAuthorizationRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/16 16:22
     */
    public function register(WebAuthorizationRequest $request)
    {
        $captchaCacheKey = 'verificationCode_' . $request->get('captcha_key');
        $captchaData = \Cache::get($captchaCacheKey);

        if (!$captchaData) {
            abort(403, '短信验证码已失效');
        }

        if (!hash_equals(strtolower($captchaData['code']), strtolower($request->get('captcha')))) {
            \Cache::forget($captchaCacheKey);
            abort(403, '短信验证码错误');
        }

        // 清除图片验证码
        \Cache::forget($captchaCacheKey);

        $user = User::create([
            'username' => $request->get('username'),
            'phone' => $captchaData['phone'] ?? "",
            'password' => $request->get('password'),
            'status' => true
        ]);

        // 初始化会员信息
        if ($user->id) {
            // 更新用户组
            $user->roles()->sync(self::DEFAULT_ROLES, false);
            $user->edit();

            // 创建用户会员记录
            $member = new Member();
            $memberInfo = array_merge([
                'user_id' => $user->id,
                'nickname' => "普通注册用户"
            ], self::DEFAULT_MEMBER);
            $member->fill($memberInfo);
            $member->edit();
        }

        $credentials = [
            'username' => $user->username,
            'password' => $request->get('password')
        ];

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            abort(403, '用户名或密码错误');
        }

        return $this->respondWithToken(auth('api')->user(), $token);
    }

    /**
     * 忘记密码
     *
     * @param WebAuthorizationRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/20 16:18
     */
    public function forget(WebAuthorizationRequest $request)
    {
        $captchaCacheKey = 'verificationCode_' . $request->get('captcha_key');
        $captchaData = \Cache::get($captchaCacheKey);

        if (!$captchaData) {
            abort(403, '短信验证码已失效');
        }

        if (!hash_equals(strtolower($captchaData['code']), strtolower($request->get('captcha')))) {
            \Cache::forget($captchaCacheKey);
            abort(403, '短信验证码错误');
        }

        // 清除短信验证码
        \Cache::forget($captchaCacheKey);

        $username = $request->get('username');

        $user = User::where('username', $username)->first();

        // 修改会员密码
        if ($user->id) {
            $user->fill([
                'password' => $request->get('password')
            ]);

            $user->edit();
        }

        return response()->json([]);
    }

    /**
     * 查询账号
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/20 16:52
     */
    public function queryUsername(FormRequest $request)
    {
        $phone = $request->get('phone');
        $openid = $request->get('openid');
        $email = $request->get('email');

        $condition = null;
        if($phone) {
            $condition = ['phone' => $phone];
        } elseif($openid) {
            $condition = ['openid' => $openid];
        }elseif($email) {
            $condition = ['email' => $email];
        }

        $user = $condition ? User::where($condition)->first() : null;

        return response()->json(['username' => $user ? $user->username : '']);
    }

    /**
     * 获取用户信息(暂时没用)
     *
     * @param EasywechatAuthorizationRequest $request
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 09:26
     */
    public function easywechatDec(EasywechatAuthorizationRequest $request)
    {

        $miniProgram = EasyWeChat::miniApp();

        try {
            $sessionKey = $request->session_key;
            $iv = $request->iv;
            $encryptedData = $request->encryptedData;
            if ($sessionKey && $iv && $encryptedData) {
                $utils = $miniProgram->getUtils();
                $userInfo = $utils->decryptSession($sessionKey, $iv, $encryptedData);
                dd($userInfo);
            } else {
                throw new AuthenticationException('参数错误，未获取用户信息');
            }
        } catch (\Exception $e) {
            throw new AuthenticationException('参数错误，未获取用户信息');
        }
    }

    /**
     * 用户登录
     *
     * @param AuthorizationRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/19 10:33
     */
    public function login(AuthorizationRequest $request)
    {
        $username = $request->get('username');
        $phoneValid = new PhoneNumber($username, 'CN');
        $credentials = [];

        // 根据账号验证判断登录方式
        $phoneValid->isValid() ? $credentials['phone'] = $username :
            (filter_var($username, FILTER_VALIDATE_EMAIL) ? $credentials['email'] = $username :
                $credentials['username'] = $username);

        // 根据登录类型对验证码进行处理
        $types = [
            'image' => [
                'name' => 'captcha',
                'tip' => '图片',
                'isPassword' => true
            ],
            'phone' => [
                'name' => 'verificationCode',
                'tip' => '短信',
                'isPassword' => false
            ]
        ];

        $type = $types[$request->get('type')] ?? $types['image'];

        $captchaCacheKey = "{$type['name']}_" . $request->get('captcha_key');
        $captchaData = \Cache::get($captchaCacheKey);

        if (!$captchaData) {
            abort(403, "{$type['tip']}验证码已失效");
        }

        if (!hash_equals(strtolower($captchaData['code']), strtolower($request->get('captcha')))) {
            \Cache::forget($captchaCacheKey);
            abort(403, '验证码错误');
        }

        // 清除图片验证码
        \Cache::forget($captchaCacheKey);

        if($type['isPassword']) {
            $credentials['password'] = $request->get('password');

            if (!$token = Auth::guard('api')->attempt($credentials)) {
                abort(403, '用户名或密码错误');
            }
        } else {
            // 验证手机号是否相同
            if ($captchaData['phone'] != $username) {
                abort(403, "手机号码错误");
            }

            $user = User::where('phone', $username)->first();
            if($user) {
                // 登录
                if(Auth::guard('api')->onceUsingId($user->id)) {
                    $token = Auth::guard('api')->tokenById($user->id);
                }
            } else {
                abort(403, '用户不存在');
            }
        }

        return $this->respondWithToken(auth('api')->user(), $token);

    }

    /**
     * 刷新令牌
     *
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/19 10:38
     */
    public function refresh()
    {
        $token = auth('api')->refresh();
        return $this->respondWithToken(auth('api')->user(), $token);
    }

    /**
     * 退出
     *
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/19 10:37
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json([]);
    }

    /**
     * 返回报文
     *
     * @param $token
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/19 10:32
     */
    protected function respondWithToken(User $user, $token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'member' => $user->member
        ]);
    }
}
