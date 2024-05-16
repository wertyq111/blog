<?php

use App\Http\Controllers\Api\Admin\MenuController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\CaptchasController;
use App\Http\Controllers\Api\QiNiuController;
use App\Http\Controllers\Api\User\UsersController;
use App\Http\Controllers\Api\User\MembersController;
use App\Http\Controllers\Api\User\MemberLevelController;
use App\Http\Controllers\Api\User\VerificationCodesController;
use App\Http\Controllers\Api\AuthorizationsController;
use App\Http\Controllers\Api\MiniProgram\WallpaperController;
use App\Http\Controllers\Api\MiniProgram\WallpaperClassifyController;
use App\Http\Controllers\Api\Admin\ServerPathController;
use App\Http\Controllers\Api\Admin\InitModelController;
use App\Http\Controllers\Api\Web\ArticlesController;
use App\Http\Controllers\Api\Web\CategoriesController;
use App\Http\Controllers\Api\Web\LabelsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User\Member;
use App\Models\User\MemberLevel;
use App\Models\Permission\Role;
use App\Models\Permission\Menu;
use App\Models\MiniProgram\WallpaperClassify;
use App\Models\MiniProgram\Wallpaper;
use App\Models\Admin\ServerPath;
use App\Models\Admin\InitModel;
use App\Models\Web\Article;
use App\Models\Web\Category;
use App\Models\Web\Label;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::name('api')->group(function () {
    // 登录类路由组
    //Route::middleware('throttle:'. config('api.rate_limits.sign'))->group(function () {
    // 获取验证码
    Route::post('verification-code/send', [VerificationCodesController::class, 'send'])
        ->name('verification-code.send');

    // socials 第三方登录
    Route::post('socials/{social_type}/authorizations', [AuthorizationsController::class, 'socialStore'])
        ->where('social_type', 'wechat')
        ->name('socials.authorizations.store');

    // easywechat 第三方登录
    Route::post('easywechat/{type}/authorizations', [AuthorizationsController::class, 'easywechatStore'])
        ->where('type', 'mini_program')
        ->name('easywechat.authorizations.store');


    // 用户注册
    Route::post('user/register', [AuthorizationsController::class, 'register'])->name('user.register');

    // 用户登录
    Route::post('user/login', [AuthorizationsController::class, 'login'])->name('user.login');

    // 刷新登录
    Route::get('user/refresh', [AuthorizationsController::class, 'refresh'])->name('user.refresh');

    // 用户退出登录
    Route::delete('user/logout', [AuthorizationsController::class, 'logout'])->name('user.logout');

    //});

    // 访问类路由组 - 限制访问次数
    //Route::middleware('throttle:'. config('api.rate_limits.access'))->group(function () {
    // 天气信息
    Route::get('weather', [WeatherController::class, 'index'])->name('weather.index');

    // 后台功能组 - 登录后才能访问的接口 - 验证 token 后会刷新 token 前端需要从响应 Header 中找到新的 token 进行替换
    Route::middleware('auth:api')->middleware('refresh.token')->group(function () {
        /** 用户接口开始 */
        // 获取用户信息
        Route::get('users/getUserInfo', [UsersController::class, 'getUserInfo'])->name('users.getUserInfo');
        // 用户列表
        Route::get('users/list', [UsersController::class, 'index'])->name('users.index');
        // 验证用户
        Route::get('users/checkUser', [UsersController::class, 'checkUser'])->name('users.checkUser');
        // 创建用户
        Route::post('users/add', [UsersController::class, 'add'])->name('users.add');
        // 重置密码
        Route::post('users/resetPwd/{user}', [UsersController::class, 'resetPwd'])->name('users.resetPwd');
        // 修改用户
        Route::post('users/status/{user}', [UsersController::class, 'status'])->name('users.status');
        // 修改用户
        Route::post('users/{user}', [UsersController::class, 'edit'])->name('users.edit');
        // 删除用户
        Route::delete('users/{user}', [UsersController::class, 'delete'])->name('users.delete');
        /** 用户接口结束 */

        /** 会员接口开始 */
        // 会员列表
        Route::get('members/index', [MembersController::class, 'index'])->name('members.index')
            ->middleware('filter.process:' . Member::class);
        // 会员信息
        Route::get('members/info', [MembersController::class, 'info'])->name('members.info');
        // 当前会员信息
        Route::get('members/user', [MembersController::class, 'user'])->name('members.user');
        // 添加会员
        Route::post('members/add', [MembersController::class, 'add'])->name('members.add');
        // 修改状态
        Route::post('members/status/{member}', [MembersController::class, 'status'])->name('members.status');
        // 修改会员
        Route::post('members/{member}', [MembersController::class, 'edit'])->name('members.edit');
        // 删除会员
        Route::delete('members/{member}', [MembersController::class, 'delete'])->name('members.delete');

        // 会员等级列表
        Route::get('member-level/index', [MemberLevelController::class, 'index'])->name('member-level.index')
            ->middleware('filter.process:' . MemberLevel::class);
        // 会员等级列表
        Route::get('member-level/list', [MemberLevelController::class, 'list'])->name('member-level.list');
        // 添加会员等级
        Route::post('member-level/add', [MemberLevelController::class, 'add'])->name('member-level.add');
        Route::delete('member-level/batchDelete', [MemberLevelController::class, 'batchDelete'])->name('member-level.batchDelete');
        // 修改状态
        Route::post('member-level/status/{memberLevel}', [MemberLevelController::class, 'status'])->name('member-level.status');
        // 修改会员等级
        Route::post('member-level/{memberLevel}', [MemberLevelController::class, 'edit'])->name('member-level.edit');
        // 删除会员等级
        Route::delete('member-level/{memberLevel}', [MemberLevelController::class, 'delete'])->name('member-level.delete');
        /** 会员接口结束 */

        /** 角色接口开始 */
        // 获取角色列表
        Route::get('role/getRoleList', [RoleController::class, 'getRoleList'])->name('role.getRoleList');
        // 角色列表
        Route::get('role/index', [RoleController::class, 'index'])->name('role.index')
            ->middleware('filter.process:' . Role::class);
        // 角色权限列表
        Route::get('role/permission/{role}', [RoleController::class, 'getPermissionList'])->name('role.getPermissionList');
        // 添加角色
        Route::post('role/add', [RoleController::class, 'add'])->name('role.add');
        // 批量删除角色
        Route::post('role/batchDelete', [RoleController::class, 'batchDelete'])->name('role.batchDelete');
        // 角色权限更新
        Route::post('role/permission/{role}', [RoleController::class, 'savePermissionList'])->name('role.savePermissionList');
        // 修改角色
        Route::post('role/{role}', [RoleController::class, 'edit'])->name('role.edit');
        // 修改角色状态
        Route::post('role/status/{role}', [RoleController::class, 'status'])->name('role.status');
        // 删除角色
        Route::delete('role/{role}', [RoleController::class, 'delete'])->name('role.delete');
        /** 角色接口结束 */

        /** 菜单接口开始 */
        // 菜单列表
        Route::get('menu/index', [MenuController::class, 'index'])->name('menu.index')
            ->middleware('filter.process:' . Menu::class);
        // 获取菜单列表
        Route::get('index/getMenuList', [MenuController::class, 'getMenuList'])->name('menu.getMenuList');
        // 菜单详情
        Route::get('menu/info/{menu}', [MenuController::class, 'info'])->name('menu.info');
        // 添加菜单
        Route::post('menu/add', [MenuController::class, 'add'])->name('menu.add');
        // 修改菜单
        Route::post('menu/{menu}', [MenuController::class, 'edit'])->name('menu.edit');
        // 删除菜单
        Route::delete('menu/{menu}', [MenuController::class, 'delete'])->name('menu.delete');
        /** 菜单接口结束 */

        /** 壁纸接口开始 */
        // 获取壁纸分类列表
        Route::get('wallpaper-classify/index', [WallpaperClassifyController::class, 'index'])
            ->name('wallpaper-classify.index')->middleware('filter.process:' . WallpaperClassify::class);
        // 获取壁纸分类
        Route::get('wallpaper-classify/list', [WallpaperClassifyController::class, 'list'])
            ->name('wallpaper-classify.list')->middleware('filter.process:' . WallpaperClassify::class);
        // 壁纸分类详情
        Route::get('wallpaper-classify/info/{classify}', [WallpaperClassifyController::class, 'info'])
            ->name('wall_pager.classify');
        // 添加壁纸分类
        Route::post('wallpaper-classify/add', [WallpaperClassifyController::class, 'add'])->name('wallpaper-classify.add');
        // 修改壁纸分类
        Route::post('wallpaper-classify/{classify}', [WallpaperClassifyController::class, 'edit'])->name('wallpaper-classify.edit');
        // 删除壁纸分类
        Route::delete('wallpaper-classify/{classify}', [WallpaperClassifyController::class, 'delete'])->name('wallpaper-classify.delete');
        // 获取壁纸列表
        Route::get('wallpaper/index', [WallpaperController::class, 'index'])->name('wallpaper.index')
            ->middleware('filter.process:' . Wallpaper::class);
        // 获取当前会员壁纸列表
        Route::get('wallpaper/user-list', [WallpaperController::class, 'userList'])->name('wallpaper.user-list')
            ->middleware('filter.process:' . Wallpaper::class);
        // 获取随机壁纸
        Route::get('wallpaper/random', [WallpaperController::class, 'random'])->name('wallpaper.random');
        // 壁纸分类详情
        Route::get('wallpaper/info/{wallpaper}', [WallpaperController::class, 'info'])
            ->name('wall_pager.classify');
        // 壁纸下载
        Route::get('wallpaper/download/{wallpaper}', [WallpaperController::class, 'download'])->name('wallpaper.download');
        // 壁纸评分
        Route::post('wallpaper/score/{wallpaper}', [WallpaperController::class, 'score'])->name('wallpaper.score');
        // 添加壁纸分类
        Route::post('wallpaper/add', [WallpaperController::class, 'add'])->name('wallpaper.add');
        // 修改壁纸分类
        Route::post('wallpaper/{wallpaper}', [WallpaperController::class, 'edit'])->name('wallpaper.edit');
        // 删除壁纸分类
        Route::delete('wallpaper/{wallpaper}', [WallpaperController::class, 'delete'])->name('wallpaper.delete');
        /** 壁纸接口结束 */

        /** 开发助手接口开始 */
        // 服务器路径列表
        Route::get('server-path/index', [ServerPathController::class, 'index'])->name('server-path.index')
            ->middleware('filter.process:' . ServerPath::class);
        // 服务器路径详情
        Route::get('server-path/{serverPath}', [ServerPathController::class, 'info'])->name('server-path.info');
        // 添加服务器路径
        Route::post('server-path/add', [ServerPathController::class, 'add'])->name('server-path.add');
        // 修改服务器路径
        Route::post('server-path/{serverPath}', [ServerPathController::class, 'edit'])->name('server-path.edit');
        // 删除服务器路径
        Route::delete('server-path/{serverPath}', [ServerPathController::class, 'delete'])->name('server-path.delete');
        // 服务器路径转换
        Route::post('server-path/convert/{serverPath}', [ServerPathController::class, 'convert'])->name('server-path.convert');
        // 模型初始化列表
        Route::get('init-model/index', [InitModelController::class, 'index'])->name('init-model.index')
            ->middleware('filter.process:' . InitModel::class);
        // 模型初始化详情
        Route::get('init-model/{initModel}', [InitModelController::class, 'info'])->name('init-model.info');
        // 添加模型初始化
        Route::post('init-model/add', [InitModelController::class, 'add'])->name('init-model.add');
        // 修改模型初始化
        Route::post('init-model/{initModel}', [InitModelController::class, 'edit'])->name('init-model.edit');
        // 删除模型初始化
        Route::delete('init-model/{initModel}', [InitModelController::class, 'delete'])->name('init-model.delete');
        // 模型初始化转换
        Route::post('init-model/convert/{initModel}', [InitModelController::class, 'convert'])->name('init-model.convert');
        /** 开发助手接口结束 */
        /** 笔记接口开始 */
        // 文章列表
        Route::get('articles/index', [ArticlesController::class, 'index'])->name('articles.index')
            ->middleware('filter.process:' . Article::class);
        // 文章所有列表
        Route::get('articles/list', [ArticlesController::class, 'list'])->name('articles.list')
            ->middleware('filter.process:' . Article::class);
        // 文章详情
        Route::get('articles/{article}', [ArticlesController::class, 'info'])->name('articles.info');
        // 文章详情(前端)
        Route::get('articles/show/{article}', [ArticlesController::class, 'show'])->name('articles.show');
        // 添加文章
        Route::post('articles/add', [ArticlesController::class, 'add'])->name('articles.add');
        // 修改文章
        Route::post('articles/{article}', [ArticlesController::class, 'edit'])->name('articles.edit');
        // 点赞文章
        Route::post('articles/good/{article}', [ArticlesController::class, 'good'])->name('articles.good');
        // 删除文章
        Route::delete('articles/{article}', [ArticlesController::class, 'delete'])->name('articles.delete');
        // 文章分类列表
        Route::get('categories/index', [CategoriesController::class, 'index'])->name('categories.index')
            ->middleware('filter.process:' . Category::class);
        // 文章分类所有列表
        Route::get('categories/list', [CategoriesController::class, 'list'])->name('categories.list');
        // 所有分类标签
        Route::get('categories/all', [CategoriesController::class, 'all'])->name('categories.all');
        // 文章分类详情
        Route::get('categories/{category}', [CategoriesController::class, 'info'])->name('categories.info');
        // 添加文章分类
        Route::post('categories/add', [CategoriesController::class, 'add'])->name('categories.add');
        // 修改文章分类
        Route::post('categories/{category}', [CategoriesController::class, 'edit'])->name('categories.edit');
        // 删除文章分类
        Route::delete('categories/{category}', [CategoriesController::class, 'delete'])->name('categories.delete');
        // 文章标签列表
        Route::get('labels/index', [LabelsController::class, 'index'])->name('labels.index')
            ->middleware('filter.process:' . Label::class);
        // 文章标签详情
        Route::get('labels/{label}', [LabelsController::class, 'info'])->name('labels.info');
        // 添加标签标签
        Route::post('labels/add', [LabelsController::class, 'add'])->name('labels.add');
        // 修改文章标签
        Route::post('labels/{label}', [LabelsController::class, 'edit'])->name('labels.edit');
        // 删除文章标签
        Route::delete('labels/{label}', [LabelsController::class, 'delete'])->name('labels.delete');
        /** 笔记接口结束 */
    });
    //});

    // 图片验证码
    Route::get('captcha', [CaptchasController::class, 'store'])->name('captcha.store');

    // 后台功能组 - 登录后才能访问的接口 - 验证 token 后会刷新 token 前端需要从响应 Header 中找到新的 token 进行替换
    Route::middleware('auth:api')->middleware('refresh.token')->group(function () {
        // 七牛云上传 token
        Route::get('qiniu/up-token', [QiNiuController::class, 'upToken'])->name('qiniu.up-token');
    });

    // 处理访问不存在的请求
    Route::fallback(function () {
        return response()->json([
            'message' => 'Page Not Found. If error persists, contact info@website.com'], 404);
    });
});
