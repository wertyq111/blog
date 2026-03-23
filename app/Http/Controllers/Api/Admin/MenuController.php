<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\MenuRequest;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Models\Permission\Menu;
use App\Services\Api\MenuService;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class MenuController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new MenuService();
    }


    /**
     * 菜单列表 - 不分页
     *
     * @param FormRequest $request
     * @param Menu $menu
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 09:52
     */
    public function index(FormRequest $request, Menu $menu)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($menu->getRequestFilters());

        $menus = QueryBuilder::for($menu)
            ->allowedFilters($allowedFilters)->orderBy('sort')->get()->toArray();

        return new BaseResource($menus);
    }

    /**
     * 菜单详情
     *
     * @param Menu $menu
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 13:28
     */
    public function info(Menu $menu)
    {
        $menu = QueryBuilder::for($menu)->findOrFail($menu->id);

        $info = $menu->toArray();
        if($info['pid'] > 0 &&  $menu->children) {
            $info['checkedList'] = array_column($menu->children->toArray(), 'sort');
        }

        return new BaseResource($info);
    }

    /**
     * 菜单列表
     *
     * @param FormRequest $request
     * @param Menu $menu
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/6 11:02
     */
    public function list(FormRequest $request, Menu $menu)
    {
        $menus = QueryBuilder::for($menu)
            ->paginate();


        return BaseResource::collection($menus);
    }

    /**
     * 添加菜单
     *
     * @param MenuRequest $request
     * @param Menu $menu
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:07
     */
    public function add(MenuRequest $request, Menu $menu)
    {
        $data = $request->all();
        try {
            // 如果存在权限菜单, 开启事务
            if (isset($data['checkedList']) && count($data['checkedList']) > 0) {
                // 开启事务
                DB::beginTransaction();
            }
            $menu->fill($data);
            $menu->edit();
            if (isset($data['checkedList']) && count($data['checkedList']) > 0) {
                $permissionContrast = MenuService::permissionContrastMap();
                $item = explode("/", $data['path']);
                // 模块名称
                $moduleName = $item[count($item) - 1];
                // 模块标题
                $moduleTitle = str_replace("管理", "", $data['title']);

                $childMenus = [];
                foreach($data['checkedList'] as $permissionSort) {
                    if (!isset($permissionContrast[$permissionSort])) {
                        continue;
                    }
                    $child['pid'] = $menu->id;
                    $child['type'] = 1;
                    $child['status'] = 1;
                    $child['sort'] = intval($permissionSort);
                    $child['target'] = $data['target'];
                    $child['title'] = str_replace("%s%", $moduleTitle, $permissionContrast[$permissionSort]['title']);
                    $child['permission'] = str_replace("%s%", $moduleName, $permissionContrast[$permissionSort]['permission']);

                    // 判断现有权限组是否已存在
                    if (in_array($permissionSort, array_column($menu->children->toArray(), 'sort'))) {
                        continue;
                    } else {
                        $childMenus[] = new Menu($child);
                    }
                }

                $menu->child()->saveMany($childMenus);
            }
        } catch (\Exception $e) {
            // 如果存在权限菜单, 回滚事务
            if (isset($data['checkedList']) && count($data['checkedList']) > 0) {
                // 回滚
                DB::rollBack();
            }
            throw $e;
        }

        // 如果存在权限菜单, 提交事务
        if (isset($data['checkedList']) && count($data['checkedList']) > 0) {
            // 提交事务
            DB::commit();
        }

        return new BaseResource($menu);

    }

    /**
     * 编辑菜单
     *
     * @param Menu $menu
     * @param FormRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:08
     */
    public function edit(Menu $menu, FormRequest $request)
    {
        $data = $request->all();

        try {
            // 如果存在权限菜单, 开启事务
            if (isset($data['checkedList']) && count($data['checkedList']) > 0) {
                // 开启事务
                DB::beginTransaction();
            }
            $menu->fill($data);
            $menu->edit();
            if (isset($data['checkedList']) && count($data['checkedList']) > 0) {
                $permissionContrast = MenuService::permissionContrastMap();
                $item = explode("/", $data['path']);
                // 模块名称
                $moduleName = $item[count($item) - 1];
                // 模块标题
                $moduleTitle = str_replace("管理", "", $data['title']);

                $childMenus = [];
                foreach($data['checkedList'] as $permissionSort) {
                    if (!isset($permissionContrast[$permissionSort])) {
                        continue;
                    }
                    $child['pid'] = $menu->id;
                    $child['type'] = 1;
                    $child['status'] = 1;
                    $child['sort'] = intval($permissionSort);
                    $child['target'] = $data['target'];
                    $child['title'] = str_replace("%s%", $moduleTitle, $permissionContrast[$permissionSort]['title']);
                    $child['permission'] = str_replace("%s%", $moduleName, $permissionContrast[$permissionSort]['permission']);

                    // 判断现有权限组是否已存在
                    if (in_array($permissionSort, array_column($menu->children->toArray(), 'sort'))) {
                        continue;
                    } else {
                        $childMenus[] = new Menu($child);
                    }
                }

                $menu->child()->saveMany($childMenus);
            }
        } catch (\Exception $e) {
            // 如果存在权限菜单, 回滚事务
            if (isset($data['checkedList']) && count($data['checkedList']) > 0) {
                // 回滚
                DB::rollBack();
            }
            throw $e;
        }

        // 如果存在权限菜单, 提交事务
        if (isset($data['checkedList']) && count($data['checkedList']) > 0) {
            // 提交事务
            DB::commit();
        }

        return new BaseResource($menu);
    }

    /**
     * 删除菜单
     *
     * @param Menu $menu
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:23
     */
    public function delete(Menu $menu)
    {
        // 批量删除子级
        $this->service->batchDeleteChildren($menu->children);

        $menu->delete();

        return response()->json([]);
    }

    /**
     * 获取全部菜单
     *
     * @param Menu $menu
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/7 14:30
     */
    public function getMenuList(Menu $menu)
    {
        $user = auth('api')->user();
        if($user->id != 1) {
            // 关键：先 preload, 防止 N+1
            $user->load('roles.menus');

            $menuIds = $user->roles
                ->flatMap(fn ($role) => $role->menus) //把每个角色的 menus 拿出来，并合并成一个“扁平的一维集合”
                ->pluck('id')
                ->unique()
                ->values();

            $menus = QueryBuilder::for(Menu::class)
                ->whereIn('id', $menuIds)
                ->where('pid', 0)
                ->with([
                    'menuChildren' => function ($query) use ($menuIds) {
                        $query->whereIn('id', $menuIds)
                            ->orderBy('sort', 'ASC');
                    }
                ])
                ->orderBy('sort', 'ASC')
                ->get();
        } else{
            $menus = QueryBuilder::for($menu)
                ->where(['pid' => 0])
                ->with(['menuChildren'])
                ->orderBy('sort', 'ASC')
                ->get();
        }

        // menuChildren替换成 children
        $menus = $this->service->convertChildrenKey($menus);

        return new BaseResource($menus);
    }
}
