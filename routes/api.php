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
use App\Http\Controllers\Api\User\AvatarController;
use App\Http\Controllers\Api\AuthorizationsController;
use App\Http\Controllers\Api\MiniProgram\WallpaperController;
use App\Http\Controllers\Api\MiniProgram\WallpaperClassifyController;
use App\Http\Controllers\Api\MiniProgram\PhotoController;
use App\Http\Controllers\Api\MiniProgram\PhotoCategoryController;
use App\Http\Controllers\Api\MiniProgram\MaterialController;
use App\Http\Controllers\Api\MiniProgram\HouseController;
use App\Http\Controllers\Api\MiniProgram\ImageController;
use App\Http\Controllers\Api\Admin\ServerPathController;
use App\Http\Controllers\Api\Admin\InitModelController;
use App\Http\Controllers\Api\Admin\PlatformScriptController;
use App\Http\Controllers\Api\Admin\WorkDailyLogController;
use App\Http\Controllers\Api\Admin\WorkDailyImageController;
use App\Http\Controllers\Api\Admin\WorkDailyTagController;
use App\Http\Controllers\Api\Admin\WorkPlatformController;
use App\Http\Controllers\Api\Admin\WorkDocController;
use App\Http\Controllers\Api\Admin\WorkDocCategoryController;
use App\Http\Controllers\Api\Admin\TodoItemController;
use App\Http\Controllers\Api\Admin\PomoTaskController;
use App\Http\Controllers\Api\Admin\PomoSettingController;
use App\Http\Controllers\Api\Admin\PomoStatsController;
use App\Http\Controllers\Api\Admin\ProductImageExtractorController;
use App\Models\Admin\PomoTask;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Web\ArticlesController;
use App\Http\Controllers\Api\Web\CategoriesController;
use App\Http\Controllers\Api\Web\LabelsController;
use App\Http\Controllers\Api\Tobacco\TobaccoSupplyController;
use App\Http\Controllers\Api\Tobacco\TobaccoCustomerController;
use App\Http\Controllers\Api\Tobacco\TobaccoOrderController;
use App\Http\Controllers\Api\Tobacco\TobaccoDesignatedController;
use App\Http\Controllers\Api\Tobacco\TobaccoSupplementController;
use App\Http\Controllers\Api\Tobacco\TobaccoYunController;
use App\Http\Controllers\Api\Tobacco\TobaccoOrderInspectController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User\Member;
use App\Models\User\MemberLevel;
use App\Models\Permission\Role;
use App\Models\Permission\Menu;
use App\Models\MiniProgram\WallpaperClassify;
use App\Models\MiniProgram\Wallpaper;
use App\Models\MiniProgram\Photo;
use App\Models\MiniProgram\PhotoCategory;
use App\Models\MiniProgram\Material;
use App\Models\MiniProgram\House;
use App\Models\Admin\ServerPath;
use App\Models\Admin\PlatformScriptRun;
use App\Models\Admin\InitModel;
use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkPlatform;
use App\Models\Admin\WorkDoc;
use App\Models\Admin\WorkDocCategory;
use App\Models\Admin\TodoItem;
use App\Models\Web\Article;
use App\Models\Web\Category;
use App\Models\Web\Label;
use App\Models\Tobacco\TobaccoSupply;
use App\Models\Tobacco\TobaccoCustomer;
use App\Models\Tobacco\TobaccoOrder;
use App\Models\Tobacco\TobaccoDesignated;
use App\Models\Tobacco\TobaccoSupplement;
use App\Models\Tobacco\TobaccoYun;

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

    // 忘记密码
    Route::post('user/forget', [AuthorizationsController::class, 'forget'])->name('user.forget');

    // 查询账号
    Route::get('user/query-username', [AuthorizationsController::class, 'queryUsername'])->name('user.query-username');

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
        // 更新当前用户资料
        Route::post('index/updateUserInfo', [UsersController::class, 'updateUserInfo'])->name('users.updateUserInfo');
        // 上传当前用户头像
        Route::post('user/avatar', [AvatarController::class, 'add'])->name('user.avatar.add');
        Route::controller(UsersController::class)
            ->prefix('users')
            ->name('users.')
            ->group(function () {
                // 用户列表
                Route::get('list', 'index')->name('index');
                // 验证用户
                Route::get('checkUser', 'checkUser')->name('checkUser');
                // 未关联会员的用户列表
                Route::get('unbound', 'unbound')->name('unbound');
                // 用户详情
                Route::get('{user}', 'show')->name('show');
                // 创建用户
                Route::post('add', 'add')->name('add');
                // 重置密码
                Route::post('resetPwd/{user}', 'resetPwd')->name('resetPwd');
                // 修改用户
                Route::post('status/{user}', 'status')->name('status');
                // 修改用户
                Route::post('{user}', 'edit')->name('edit');
                // 删除用户
                Route::delete('{user}', 'delete')->name('delete');
            });
        /** 用户接口结束 */

        /** 会员接口开始 */
        Route::controller(MembersController::class)
            ->prefix('members')
            ->name('members.')
            ->group(function () {
                // 会员列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . Member::class);
                // 会员信息
                Route::get('info', 'info')->name('info');
                // 当前会员信息
                Route::get('user', 'user')->name('user');
                // 添加会员
                Route::post('add', 'add')->name('add');
                // 修改状态
                Route::post('status/{member}', 'status')->name('status');
                // 修改会员
                Route::post('{member}', 'edit')->name('edit');
                // 删除会员
                Route::delete('{member}', 'delete')->name('delete');
            });

        Route::controller(MemberLevelController::class)
            ->prefix('member-level')
            ->name('member-level.')
            ->group(function () {
                // 会员等级列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . MemberLevel::class);
                // 会员等级列表
                Route::get('list', 'list')->name('list');
                // 添加会员等级
                Route::post('add', 'add')->name('add');
                // 批量删除
                Route::post('delete', 'batchDelete')->name('batch-delete');
                // 修改状态
                Route::post('status/{memberLevel}', 'status')->name('status');
                // 修改会员等级
                Route::post('{memberLevel}', 'edit')->name('edit');
                // 删除会员等级
                Route::delete('{memberLevel}', 'delete')->name('delete');
            });
        /** 会员接口结束 */

        /** 角色接口开始 */
        Route::controller(RoleController::class)
            ->prefix('role')
            ->name('role.')
            ->group(function () {
                // 获取角色列表
                Route::get('getRoleList', 'getRoleList')->name('getRoleList');
                // 角色列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . Role::class);
                // 角色权限列表
                Route::get('permission/{role}', 'getPermissionList')->name('getPermissionList');
                // 添加角色
                Route::post('add', 'add')->name('add');
                // 批量删除角色
                Route::post('delete', 'batchDelete')->name('batch-delete');
                // 角色权限更新
                Route::post('permission/{role}', 'savePermissionList')->name('savePermissionList');
                // 修改角色
                Route::post('{role}', 'edit')->name('edit');
                // 修改角色状态
                Route::post('status/{role}', 'status')->name('status');
                // 删除角色
                Route::delete('{role}', 'delete')->name('delete');
            });
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
        Route::controller(ServerPathController::class)
            ->prefix('server-path')
            ->name('server-path.')
            ->group(function () {
                // 服务器路径列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . ServerPath::class);
                // 服务器路径详情
                Route::get('{serverPath}', 'info')->name('info');
                // 添加服务器路径
                Route::post('add', 'add')->name('add');
                // 兼容旧版前端删除服务器路径（支持单个/批量）
                Route::post('delete', 'batchDelete')->name('batch-delete');
                // 修改服务器路径
                Route::post('{serverPath}', 'edit')->name('edit');
                // 删除服务器路径
                Route::delete('{serverPath}', 'delete')->name('delete');
                // 服务器路径转换
                Route::post('convert/{serverPath}', 'convert')->name('convert');
            });
        Route::controller(InitModelController::class)
            ->prefix('init-model')
            ->name('init-model.')
            ->group(function () {
                // 模型初始化列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . InitModel::class);
                // 模型初始化详情
                Route::get('{initModel}', 'info')->name('info');
                // 添加模型初始化
                Route::post('add', 'add')->name('add');
                // 兼容旧版前端删除模型初始化（支持单个/批量）
                Route::post('delete', 'batchDelete')->name('batch-delete');
                // 修改模型初始化
                Route::post('{initModel}', 'edit')->name('edit');
                // 删除模型初始化
                Route::delete('{initModel}', 'delete')->name('delete');
                // 模型初始化转换
                Route::post('convert/{initModel}', 'convert')->name('convert');
            });

        Route::controller(PlatformScriptController::class)
            ->prefix('platform-script')
            ->name('platform-script.')
            ->group(function () {
                // 平台脚本执行记录列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . PlatformScriptRun::class);
                // 平台脚本解析预览（不发送）
                Route::post('preview', 'preview')->name('preview');
                // 平台脚本执行推送
                Route::post('run', 'run')->name('run');
                // 平台脚本查询授信进度
                Route::post('progress', 'progress')->name('progress');
                // 平台脚本推送确认担保
                Route::post('confirm-guarantee', 'confirmGuarantee')->name('confirmGuarantee');
                // 平台脚本执行记录详情
                Route::get('{platformScriptRun}', 'info')->name('info');
            });

        Route::controller(ProductImageExtractorController::class)
            ->prefix('design')
            ->name('design.product-image-extractor.')
            ->group(function () {
                // 支持的商品图片平台
                Route::get('product-image-extractor/platforms', 'platforms')
                    ->name('platforms');
                // 提取商品图片
                Route::post('product-image-extractor/extract', 'extract')
                    ->name('extract')
                    ->middleware('throttle:10,1');
                // 下载商品图片压缩包
                Route::post('product-image-extractor/download', 'download')
                    ->name('download')
                    ->middleware('throttle:10,1');
            });

        // 工作台仪表盘统计
        Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        Route::controller(WorkPlatformController::class)
            ->prefix('work-platform')
            ->name('work-platform.')
            ->group(function () {
                // 平台列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . WorkPlatform::class);
                // 平台列表（不分页）
                Route::get('list', 'list')->name('list');
                // 批量保存排序
                Route::post('reorder', 'reorder')->name('reorder');
                // 平台详情
                Route::get('{workPlatform}', 'info')->name('info');
                // 添加平台
                Route::post('add', 'add')->name('add');
                // 修改平台
                Route::post('{workPlatform}', 'edit')->name('edit');
                // 删除平台
                Route::delete('{workPlatform}', 'delete')->name('delete');
            });


        Route::controller(WorkDailyTagController::class)
            ->prefix('work-daily-tag')
            ->name('work-daily-tag.')
            ->group(function () {
                // 工作日常标签
                Route::get('list', 'list')->name('list');
                Route::post('add', 'add')->name('add');
                Route::delete('{workDailyTag}', 'delete')->name('delete');
            });

        // 牛马日常列表
        Route::get('work-daily/index', [WorkDailyLogController::class, 'index'])->name('work-daily.index')
            ->middleware('filter.process:' . WorkDailyLog::class);
        // 导入牛马日常
        Route::post('work-daily/import', [WorkDailyLogController::class, 'import'])->name('work-daily.import');
        // 上传牛马日常图片
        Route::post('work-daily/image', [WorkDailyImageController::class, 'store'])->name('work-daily.image.store');
        Route::controller(WorkDailyLogController::class)
            ->prefix('work-daily')
            ->name('work-daily.')
            ->group(function () {
                // 牛马日常详情
                Route::get('{workDailyLog}', 'info')->name('info');
                // 添加牛马日常
                Route::post('add', 'add')->name('add');
                // 修改牛马日常
                Route::post('{workDailyLog}', 'edit')->name('edit');
                // 删除牛马日常
                Route::delete('{workDailyLog}', 'delete')->name('delete');
                // 月报导出
                Route::get('report/month', 'reportMonth')->name('report-month');
                // 周报导出
                Route::get('report/week', 'reportWeek')->name('report-week');
                // 年报导出
                Route::get('report/year', 'reportYear')->name('report-year');
                // 异步报表导出
                Route::post('report/export', 'reportExport')->name('report-export');
                // 当前报表导出任务
                Route::get('report/export/current', 'currentReportExport')->name('report-export-current');
                // 报表导出任务列表（当前用户）
                Route::get('report/export/list', 'listReportExports')->name('report-export-list');
                // 报表导出任务详情
                Route::get('report/export/{export}', 'reportExportInfo')->name('report-export-info');
                // 报表导出文件下载
                Route::get('report/export/{export}/download', 'downloadReportExport')->name('report-export-download');
                // 报表导出内容编辑保存
                Route::post('report/export/{export}/content', 'updateReportExportContent')->name('report-export-content');
                // 报表导出记录删除
                Route::delete('report/export/{export}', 'deleteReportExport')->name('report-export-delete');
                // OpenClaw 报表模型列表
                Route::get('report/models', 'reportModels')->name('report-models');
            });

        Route::controller(WorkDocCategoryController::class)
            ->prefix('work-doc-category')
            ->name('work-doc-category.')
            ->group(function () {
                // 牛马文档分类列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . WorkDocCategory::class);
                // 牛马文档分类列表（不分页）
                Route::get('list', 'list')->name('list');
                // 牛马文档分类详情
                Route::get('{category}', 'info')->name('info');
                // 添加牛马文档分类
                Route::post('add', 'add')->name('add');
                // 牛马文档分类拖拽排序
                Route::post('reorder', 'reorder')->name('reorder');
                // 修改牛马文档分类
                Route::post('{category}', 'edit')->name('edit');
                // 删除牛马文档分类
                Route::delete('{category}', 'delete')->name('delete');
            });

        Route::controller(WorkDocController::class)
            ->prefix('work-doc')
            ->name('work-doc.')
            ->group(function () {
                // 牛马文档列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . WorkDoc::class);
                // 牛马文档导出带样式 HTML
                Route::get('{workDoc}/export', 'export')->name('export');
                // 牛马文档详情
                Route::get('{workDoc}', 'info')->name('info');
                // 添加牛马文档
                Route::post('add', 'add')->name('add');
                // 修改牛马文档
                Route::post('{workDoc}', 'edit')->name('edit');
                // 删除牛马文档
                Route::delete('{workDoc}', 'delete')->name('delete');
            });

        Route::controller(TodoItemController::class)
            ->prefix('todo')
            ->name('todo.')
            ->group(function () {
                // 待办列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . TodoItem::class);
                Route::get('statistics', 'statistics')->name('statistics');
                Route::get('{todoItem}', 'info')->name('info');
                Route::post('add', 'add')->name('add');
                Route::post('status/{todoItem}', 'updateStatus')->name('update-status');
                Route::post('{todoItem}', 'edit')->name('edit');
                Route::delete('{todoItem}', 'delete')->name('delete');
            });

        /** 番茄钟 + 提醒清单接口开始 */
        // 番茄钟设置：读取 / 保存
        Route::get('pomo/setting', [PomoSettingController::class, 'show'])->name('pomo.setting.show');
        Route::post('pomo/setting', [PomoSettingController::class, 'save'])->name('pomo.setting.save');
        Route::controller(PomoTaskController::class)
            ->prefix('pomo')
            ->name('pomo.task.')
            ->group(function () {
                // 提醒清单任务列表
                Route::get('task/index', 'index')->name('index')
                    ->middleware('filter.process:' . PomoTask::class);
                // 勾选完成 / 番茄数 +1 / 添加 / 批量删除（须排在通配 {pomoTask} 之前）
                Route::post('task/toggle-done/{pomoTask}', 'toggleDone')->name('toggle-done');
                Route::post('task/increment/{pomoTask}', 'increment')->name('increment');
                Route::post('task/add', 'add')->name('add');
                Route::post('task/delete', 'batchDelete')->name('batch-delete');
                // 任务详情 / 编辑 / 删除
                Route::get('task/{pomoTask}', 'info')->name('info');
                Route::post('task/{pomoTask}', 'edit')->name('edit');
                Route::delete('task/{pomoTask}', 'delete')->name('delete');
            });
        // 完成段记录 / 近 7 天统计
        Route::post('pomo/session', [PomoStatsController::class, 'storeSession'])->name('pomo.session.store');
        Route::get('pomo/stats/week', [PomoStatsController::class, 'week'])->name('pomo.stats.week');
        /** 番茄钟 + 提醒清单接口结束 */
        /** 开发助手接口结束 */
        /** 笔记接口开始 */
        Route::controller(ArticlesController::class)
            ->prefix('articles')
            ->name('articles.')
            ->group(function () {
                // 文章列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . Article::class);
                // 文章所有列表
                Route::get('list', 'list')->name('list')
                    ->middleware('filter.process:' . Article::class);
                // 文章详情
                Route::get('{article}', 'info')->name('info');
                // 文章详情(前端)
                Route::get('show/{article}', 'show')->name('show');
                // 添加文章
                Route::post('add', 'add')->name('add');
                // 修改文章
                Route::post('{article}', 'edit')->name('edit');
                // 点赞文章
                Route::post('good/{article}', 'good')->name('good');
                // 删除文章
                Route::delete('{article}', 'delete')->name('delete');
            });
        Route::controller(CategoriesController::class)
            ->prefix('categories')
            ->name('categories.')
            ->group(function () {
                // 文章分类列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . Category::class);
                // 文章分类所有列表
                Route::get('list', 'list')->name('list');
                // 所有分类标签
                Route::get('all', 'all')->name('all');
                // 文章分类详情
                Route::get('{category}', 'info')->name('info');
                // 添加文章分类
                Route::post('add', 'add')->name('add');
                // 修改文章分类
                Route::post('{category}', 'edit')->name('edit');
                // 删除文章分类
                Route::delete('{category}', 'delete')->name('delete');
            });
        Route::controller(LabelsController::class)
            ->prefix('labels')
            ->name('labels.')
            ->group(function () {
                // 文章标签列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . Label::class);
                // 文章标签详情
                Route::get('{label}', 'info')->name('info');
                // 添加标签标签
                Route::post('add', 'add')->name('add');
                // 修改文章标签
                Route::post('{label}', 'edit')->name('edit');
                // 删除文章标签
                Route::delete('{label}', 'delete')->name('delete');
            });
        /** 笔记接口结束 */

        /** 相册接口开始 */
        Route::controller(PhotoController::class)
            ->prefix('photo')
            ->name('photo.')
            ->group(function () {
                // 相册列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . Photo::class);
                // 相册所有列表
                Route::get('list', 'list')->name('list');
                // 精选照片
                Route::get('refine', 'refine')->name('refine');
                // 相册详情
                Route::get('{photo}', 'info')->name('info');
                // 添加相册
                Route::post('add', 'add')->name('add');
                // 批量删除
                Route::post('delete', 'batchDelete')->name('batch-delete');
                // 修改相册
                Route::post('{photo}', 'edit')->name('edit');
                // 删除相册
                Route::delete('{photo}', 'delete')->name('delete');
            });
        Route::controller(PhotoCategoryController::class)
            ->prefix('photo-categories')
            ->name('photo-categories.')
            ->group(function () {
                // 相册分类列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . PhotoCategory::class);
                // 相册分类所有列表
                Route::get('list', 'list')->name('list');
                // 置顶相册
                Route::get('new', 'new')->name('new');
                // 校验相册分类
                Route::get('check', 'check')->name('check');
                // 相册分类详情
                Route::get('{category}', 'info')->name('info');
                // 添加相册分类
                Route::post('add', 'add')->name('add');
                // 修改相册分类
                Route::post('{category}', 'edit')->name('edit');
                // 删除相册分类
                Route::delete('{category}', 'delete')->name('delete');
            });
        /** 相册接口结束 */
        /** 房屋接口开始 */
        Route::controller(HouseController::class)
            ->prefix('house')
            ->name('house.')
            ->group(function () {
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . House::class);
                // 所有列表
                Route::get('list', 'list')->name('list');
                // 校验
                Route::get('check', 'check')->name('check');
                // 详情
                Route::get('{house}', 'info')->name('info');
                // 添加
                Route::post('add', 'add')->name('add');
                // 修改
                Route::post('{house}', 'edit')->name('edit');
                // 删除
                Route::delete('{house}', 'delete')->name('delete');
            });
        /** 房屋接口结束 */
        /** 物料接口开始 */
        Route::controller(MaterialController::class)
            ->prefix('material')
            ->name('material.')
            ->group(function () {
                // 列表
                Route::get('index', 'index')->name('index')
                    ->middleware('filter.process:' . Material::class);
                // 所有列表
                Route::get('list', 'list')->name('list')
                    ->middleware('filter.process:' . Material::class);
                // 校验
                Route::get('check', 'check')->name('check');
                // 详情
                Route::get('{material}', 'info')->name('info');
                // 添加
                Route::post('add', 'add')->name('add');
                // 批量删除
                Route::post('delete', 'batchDelete')->name('batch-delete');
                // 修改
                Route::post('{material}', 'edit')->name('edit');
                // 删除
                Route::delete('{material}', 'delete')->name('delete');
            });
        /** 物料接口结束 */

        /** 图片处理接口开始 */
        // 删除
        Route::delete('image/delete', [ImageController::class, 'delete'])->name('image.delete');
        /** 图片处理接口结束 */

        /** 烟草接口开始 */
        // 客户
        Route::group(['prefix'=>'tobacco-customer','as'=>'tobacco-customer.'], function () {
            // 列表
            Route::get('index', [TobaccoCustomerController::class, 'index'])->name('index')
                ->middleware('filter.process:' . TobaccoCustomer::class);
            // 导入
            Route::post('import', [TobaccoCustomerController::class, 'import'])->name('import');
            // 动态列
            Route::get('getColumns', [TobaccoCustomerController::class, 'getColumns'])->name('getColumns');
        });
        // 订货
        Route::group(['prefix'=>'tobacco-order','as'=>'tobacco-order.'], function () {
            // 列表
            Route::get('index', [TobaccoOrderController::class, 'index'])->name('index')
                ->middleware('filter.process:' . TobaccoOrder::class);
            // 导入
            Route::post('import', [TobaccoOrderController::class, 'import'])->name('import');
            // 动态列
            Route::get('getColumns', [TobaccoOrderController::class, 'getColumns'])->name('getColumns');
        });
        // 1024定点
        Route::group(['prefix'=>'tobacco-designated','as'=>'tobacco-designated.'], function () {
            // 列表
            Route::get('index', [TobaccoDesignatedController::class, 'index'])->name('index')
                ->middleware('filter.process:' . TobaccoDesignated::class);
            // 导入
            Route::post('import', [TobaccoDesignatedController::class, 'import'])->name('import');
            // 动态列
            Route::get('getColumns', [TobaccoDesignatedController::class, 'getColumns'])->name('getColumns');
        });
        // 补供供货
        Route::group(['prefix'=>'tobacco-supplement','as'=>'tobacco-supplement.'], function () {
            // 列表
            Route::get('index', [TobaccoSupplementController::class, 'index'])->name('index')
                ->middleware('filter.process:' . TobaccoSupplement::class);
            // 导入
            Route::post('import', [TobaccoSupplementController::class, 'import'])->name('import');
            // 动态列
            Route::get('getColumns', [TobaccoSupplementController::class, 'getColumns'])->name('getColumns');
        });
        // 云烟补供
        Route::group(['prefix'=>'tobacco-yun','as'=>'tobacco-yun.'], function () {
            // 列表
            Route::get('index', [TobaccoYunController::class, 'index'])->name('index')
                ->middleware('filter.process:' . TobaccoYun::class);
            // 导入
            Route::post('import', [TobaccoYunController::class, 'import'])->name('import');
            // 动态列
            Route::get('getColumns', [TobaccoYunController::class, 'getColumns'])->name('getColumns');
        });
        // 供货限量
        Route::group(['prefix'=>'tobacco-supply','as'=>'tobacco-supply.'], function () {
            // 列表
            Route::get('index', [TobaccoSupplyController::class, 'index'])->name('index')
                ->middleware('filter.process:' . TobaccoSupply::class);
            // 导入
            Route::post('import', [TobaccoSupplyController::class, 'import'])->name('import');
            // 统计
            Route::get('statistics', [TobaccoSupplyController::class, 'statistics'])->name('statistics');
            // 动态列
            Route::get('getColumns', [TobaccoSupplyController::class, 'getColumns'])->name('getColumns');
        });

        // 订货检查模型
        Route::get('tobacco-order-inspect/index', [TobaccoOrderInspectController::class, 'index'])->name('tobacco-order-inspect.index');
        /** 烟草接口结束 */
    });
    //});

    // 图片验证码
    Route::get('captcha', [CaptchasController::class, 'store'])->name('captcha.store');

    // 后台功能组 - 登录后才能访问的接口 - 验证 token 后会刷新 token 前端需要从响应 Header 中找到新的 token 进行替换
    Route::middleware('auth:api')->middleware('refresh.token')->group(function () {
        // 七牛云上传 token
        Route::get('qiniu/up-token', [QiNiuController::class, 'upToken'])->name('qiniu.up-token');
        // 七牛云私有图片
        Route::get('qiniu/private-url', [QiNiuController::class, 'privateUrl'])->name('qiniu.private-url');
    });

    // 处理访问不存在的请求
    Route::fallback(function () {
        return response()->json([
            'message' => 'Page Not Found. If error persists, contact info@website.com'], 404);
    });
});
