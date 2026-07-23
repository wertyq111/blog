<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPlatformScriptMenu extends Migration
{
    /**
     * 在「开发管理」下新增「平台脚本」菜单。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function up(): void
    {
        $developId = $this->developMenuId();
        if (!$developId) {
            return;
        }

        $menuId = $this->upsertMenu('/develop/platform-script', [
            'pid' => $developId,
            'title' => '平台脚本',
            'icon' => 'el-icon-s-promotion',
            'component' => '/develop/platform-script',
            'target' => '_self',
            'permission' => 'dev:platformScript:view',
            'type' => 0,
            'status' => 1,
            'hide' => 0,
            'note' => '',
            'sort' => 70,
        ]);

        $this->grantToAdminRoles([$menuId]);
    }

    /**
     * 删除「平台脚本」菜单。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function down(): void
    {
        $ids = DB::table('menus')
            ->where('path', '/develop/platform-script')
            ->pluck('id')
            ->all();

        if (!$ids) {
            return;
        }

        DB::table('role_menu')->whereIn('menu_id', $ids)->delete();
        DB::table('menus')->whereIn('id', $ids)->delete();
    }

    /**
     * 查询「开发管理」父菜单 id。
     *
     * @return int|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    private function developMenuId(): ?int
    {
        $develop = DB::table('menus')
            ->where('path', '/develop')
            ->where('type', 0)
            ->first();

        return $develop ? (int) $develop->id : null;
    }

    /**
     * 新增或更新菜单。
     *
     * @param string $path
     * @param array $data
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    private function upsertMenu(string $path, array $data): int
    {
        $now = time();
        $existing = DB::table('menus')
            ->where('path', $path)
            ->where('type', 0)
            ->first();

        $payload = array_merge($data, [
            'path' => $path,
            'update_user' => 0,
            'updated_at' => $now,
            'deleted_at' => 0,
        ]);

        if ($existing) {
            DB::table('menus')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        return (int) DB::table('menus')->insertGetId(array_merge($payload, [
            'create_user' => 0,
            'created_at' => $now,
        ]));
    }

    /**
     * 将菜单授权给管理员角色。
     *
     * @param array $menuIds
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    private function grantToAdminRoles(array $menuIds): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('code', ['super', 'admin'])
            ->pluck('id')
            ->all();

        if (!$roleIds) {
            return;
        }

        foreach ($roleIds as $roleId) {
            foreach ($menuIds as $menuId) {
                $exists = DB::table('role_menu')
                    ->where('role_id', $roleId)
                    ->where('menu_id', $menuId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_menu')->insert([
                        'role_id' => $roleId,
                        'menu_id' => $menuId,
                    ]);
                }
            }
        }
    }
}
