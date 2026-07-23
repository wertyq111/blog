<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddDesignProductImageExtractorMenu extends Migration
{
    /**
     * 新增产品图片提取菜单。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function up(): void
    {
        $designId = $this->upsertMenu('/design', [
            'pid' => 0,
            'title' => '设计管理',
            'icon' => 'el-icon-picture-outline',
            'component' => '',
            'target' => '_self',
            'permission' => '',
            'type' => 0,
            'status' => 1,
            'hide' => 0,
            'note' => '',
            'sort' => 45,
        ]);

        $extractorId = $this->upsertMenu('/design/product-image-extractor', [
            'pid' => $designId,
            'title' => '商品图片提取',
            'icon' => 'el-icon-download',
            'component' => '/design/product-image-extractor',
            'target' => '_self',
            'permission' => 'design:productImageExtractor:view',
            'type' => 0,
            'status' => 1,
            'hide' => 0,
            'note' => '',
            'sort' => 20,
        ]);

        $this->grantToAdminRoles([$designId, $extractorId]);
    }

    /**
     * 删除产品图片提取菜单。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function down(): void
    {
        $ids = DB::table('menus')
            ->where('path', '/design/product-image-extractor')
            ->pluck('id')
            ->all();

        if (!$ids) {
            return;
        }

        DB::table('role_menu')->whereIn('menu_id', $ids)->delete();
        DB::table('menus')->whereIn('id', $ids)->delete();
    }

    /**
     * 新增或更新菜单。
     *
     * @param string $path
     * @param array $data
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
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
     * @date 2026/7/20
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
