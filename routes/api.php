<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Web\InfoController;
use App\Http\Controllers\Api\Web\CategoriesController;
use \App\Http\Controllers\Api\Web\LabelsController;
use App\Http\Controllers\Api\Web\ArticlesController;
use App\Http\Controllers\Api\Web\MembersController;
use App\Http\Controllers\Api\User\VerificationCodesController;
use App\Http\Controllers\Api\User\UsersController;
use App\Http\Controllers\Api\CaptchasController;
use App\Http\Controllers\Api\QiNiuController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\User\MembersController as AdminMemberController;

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

Route::name('api')->group(function() {
    // 登录类路由组
    Route::middleware('throttle:'. config('api.rate_limits.sign'))->group(function () {
        // 获取验证码
        Route::post('verification-code/send', [VerificationCodesController::class, 'send'])
            ->name('verification-code.send');

        // 用户注册
        Route::post('user/register', [UsersController::class, 'register'])->name('user.register');

        // 用户登录
        Route::post('user/login', [UsersController::class, 'login'])->name('user.login');

    });

    // 访问类路由组
    Route::middleware('throttle:'. config('api.rate_limits.access'))->group(function () {
        // 网站信息
        Route::get('web-info', [InfoController::class, 'index'])->name('web-info.index');

        // 网站分类
        Route::get('web-categories', [CategoriesController::class, 'index'])->name('web-categories.index');

        // 网站标签
        Route::get('web-labels', [LabelsController::class, 'index'])->name('web-labels.index');

        // 文章列表
        Route::get('articles', [ArticlesController::class, 'index'])->name('articles.index');

        // 会员打赏列表
        Route::get('web-members/admires', [MembersController::class, 'admires'])->name('web-members.admires');

        // 后台功能组 - 登录后才能访问的接口 - 验证 token 后会刷新 token 前端需要从响应 Header 中找到新的 token 进行替换
        Route::middleware('auth:api')->middleware('refresh.token')->group(function() {
            /** 基本信息开始 */
            // 编辑资源接口
            Route::patch('resource/edit', [ResourceController::class, 'edit'])->name('resource.edit');
            // 编辑网站信息
            Route::patch('web-info/edit', [InfoController::class, 'edit'])->name('web-info.edit');
            /** 基本信息结束 */
            /** 用户会员信息开始 */
            // 用户列表
            Route::get('users/list', [UsersController::class, 'index'])->name('users.index');
            // 修改用户状态
            Route::patch('users/status', [UsersController::class, 'status'])->name('users.status');
            // 修改会员打赏
            Route::patch('member/admire', [AdminMemberController::class, 'updateAdmire'])->name('member.admire');
            /** 用户信息结束 */
            /** 分类标签开始 */
            // 添加分类
            Route::post('web/category', [CategoriesController::class, 'add'])->name('web.category.add');
            // 修改分类
            Route::patch('web/category/{category}', [CategoriesController::class, 'edit'])->name('web.category.edit');
            // 删除分类
            Route::delete('web/category/{category}', [CategoriesController::class, 'delete'])->name('web.category.delete');
            // 添加标签
            Route::post('web/label', [LabelsController::class, 'add'])->name('web.label.add');
            // 修改标签
            Route::patch('web/label/{label}', [LabelsController::class, 'edit'])->name('web.label.edit');
            // 删除标签
            Route::delete('web/label/{label}', [LabelsController::class, 'delete'])->name('web.label.delete');
            /** 分类标签结束 */
        });
    });

    // 图片验证码
    Route::post('captcha', [CaptchasController::class, 'store'])->name('captcha.store');

    // 后台功能组 - 登录后才能访问的接口 - 验证 token 后会刷新 token 前端需要从响应 Header 中找到新的 token 进行替换
    Route::middleware('auth:api')->middleware('refresh.token')->group(function() {
        // 七牛云上传 token
        Route::get('qiniu/up-token', [QiNiuController::class, 'upToken'])->name('qiniu.up-token');
    });
});
