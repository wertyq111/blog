<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Web\InfoController;
use App\Http\Controllers\Api\Web\CategoriesController;
use App\Http\Controllers\Api\Web\ArticlesController;

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

Route::middleware('throttle:' . config('api.rate_limits.access'))->name('api')->group(function() {
    // 网站信息
    Route::get('web-info', [InfoController::class, 'index'])->name('web-info.index');


    // 网站分类
    Route::get('web-categories', [CategoriesController::class, 'index'])->name('web-categories.index');

    // 文章列表
    Route::post('articles', [ArticlesController::class, 'index'])->name('articles.index');
});
