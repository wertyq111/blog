<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\RoleRequest;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Models\Permission\Menu;
use App\Models\Permission\Role;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends Controller
{
    public function index(FormRequest $request, Role $role)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($role->getRequestFilters());

        $roles = QueryBuilder::for($role)
            ->allowedIncludes('users', 'menus')
            ->allowedFilters($allowedFilters)->paginate();

        return BaseResource::collection($roles);
    }

    /**
     * 角色列表
     *
     * @param FormRequest $request
     * @param Menu $menu
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/6 11:02
     */
    public function list(FormRequest $request, Role $role)
    {
        $roles = QueryBuilder::for($role)
            ->paginate();

        return BaseResource::collection($roles);
    }

    /**
     * 修改状态
     *
     * @param RoleRequest $request
     * @param Role $role
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:05
     */
    public function status(RoleRequest $request, Role $role)
    {
        $role = $role->find($request->get('id'));
        $role->status = $request->get('status');
        $role->edit();

        return new BaseResource($role);
    }

    /**
     * 添加角色
     *
     * @param RoleRequest $request
     * @param Role $role
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:07
     */
    public function add(RoleRequest $request, Role $role)
    {
        $data = $request->getSnakeRequest();

        $role->fill($data);

        $role->edit();

        return new BaseResource($role);

    }

    /**
     * 编辑角色
     *
     * @param Role $role
     * @param FormRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:08
     */
    public function edit(Role $role, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        $role->fill($data);

        $role->edit();

        return new BaseResource($role);
    }

    /**
     * 删除角色
     *
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:23
     */
    public function delete(Role $role)
    {
        $role->delete();

        // 清除角色与用户关系中间表记录
        $role->users()->detach();
        // 清除角色月菜单关系中间表记录
        $role->menus()->detach();

        return response()->json([]);
    }

    /**
     * 批量删除
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:32
     */
    public function batchDelete(FormRequest $request, Role $role)
    {
        $ids = $request->get('id');
        foreach($ids as $id) {
            $this->delete($role->find($id));
        }

        return response()->json([]);
    }

    /**
     * 获取全部角色
     *
     * @param Menu $menu
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/7 14:30
     */
    public function getRoleList(Role $role)
    {
        $menus = QueryBuilder::for($role)->get()->toArray();

        return new BaseResource($menus);
    }

    /**
     * 获取权限列表
     *
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 15:08
     */
    public function getPermissionList(Role $role)
    {
        $menus = new Menu();
        $permissionList = $menus->orderBy('id', 'ASC')->get()->toArray();
        foreach($permissionList as &$permission) {
            foreach($role->menus as $menu) {
                if($menu->id == $permission['id']) {
                    $permission['checked'] = $permission['open'] = true;
                }
            }
        }
        unset($permission);

        return response()->json($permissionList);
    }

    /**
     * 更新权限列表
     *
     * @param Role $role
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 15:08
     */
    public function savePermissionList(Role $role, FormRequest $request)
    {
        // 清空角色下的所有权限
        $role->menus()->detach();
        // 更新新的权限
        $role->menus()->sync($request->get('menu_id'),false);

        return response()->json([]);
    }
}
