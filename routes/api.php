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
use App\Models\Web\Article;

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
        Route::get('web-categories', [CategoriesController::class, 'list'])->name('web-categories.list');

        // 网站标签
        Route::get('web-labels', [LabelsController::class, 'list'])->name('web-labels.list');

        // 文章列表
        Route::get('web-articles', [ArticlesController::class, 'list'])
            ->name('web-articles.list')->middleware('filter.process:'. Article::class);
        // 文章详情
        Route::get('web-article/{article}', [ArticlesController::class, 'show'])->name('web-article.show');

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
            Route::post('users/list', [UsersController::class, 'index'])->name('users.index');
            // 修改用户状态
            Route::patch('users/status', [UsersController::class, 'status'])->name('users.status');
            // 修改会员打赏
            Route::patch('member/admire', [AdminMemberController::class, 'updateAdmire'])->name('member.admire');
            // 用户上传头像
            Route::post('member/avatar', [MembersController::class, 'avatar'])->name('member.avatar');
            /** 用户信息结束 */
            /** 分类标签开始 */
            // 分类列表
            Route::post('web/categories', [CategoriesController::class, 'index'])->name('web.category.index');
            // 添加分类
            Route::post('web/category', [CategoriesController::class, 'add'])->name('web.category.add');
            // 修改分类
            Route::patch('web/category/{category}', [CategoriesController::class, 'edit'])->name('web.category.edit');
            // 删除分类
            Route::delete('web/category/{category}', [CategoriesController::class, 'delete'])->name('web.category.delete');
            // 标签列表
            Route::get('web/labels', [LabelsController::class, 'index'])->name('web.label.index');
            // 添加标签
            Route::post('web/label', [LabelsController::class, 'add'])->name('web.label.add');
            // 修改标签
            Route::patch('web/label/{label}', [LabelsController::class, 'edit'])->name('web.label.edit');
            // 删除标签
            Route::delete('web/label/{label}', [LabelsController::class, 'delete'])->name('web.label.delete');
            // 获取所有分类和标签
            Route::get('web/categories-labels', [CategoriesController::class, 'all'])->name('web.category.all');
            /** 分类标签结束 */
            /** 文章开始 */
            // 文章列表
            Route::post('web/articles', [ArticlesController::class, 'index'])
                ->name('web.articles.index')->middleware('filter.process:'. Article::class);
            // 文章详情
            Route::get('web/article/{article}', [ArticlesController::class, 'detail'])->name('web.article.detail');
            // 添加文章
            Route::post('web/article', [ArticlesController::class, 'add'])->name('web.article.add');
            // 修改文章
            Route::patch('web/article/{article}', [ArticlesController::class, 'edit'])->name('web.article.edit');
            // 删除文章
            Route::delete('web/article/{article}', [ArticlesController::class, 'delete'])->name('web.article.delete');
            // 文章状态
            Route::patch('web/article/status/{article}', [ArticlesController::class, 'status'])->name('web.article.status');
            /** 文章结束 */
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
