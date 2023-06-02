<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Web\InfoController;
use App\Http\Controllers\Api\Web\CategoriesController;
use App\Http\Controllers\Api\Web\ArticlesController;
use App\Http\Controllers\Api\Web\MembersController;
use App\Http\Controllers\Api\User\VerificationCodesController;
use App\Http\Controllers\Api\User\UsersController;
use App\Http\Controllers\Api\CaptchasController;

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

        // 文章列表
        Route::post('articles', [ArticlesController::class, 'index'])->name('articles.index');

        // 会员打赏列表
        Route::get('web-members/admires', [MembersController::class, 'admires'])->name('web-members.admires');
    });

    // 图片验证码
    Route::post('captcha', [CaptchasController::class, 'store'])->name('captcha.store');
});
