<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddDesignImageProcessMenu extends Migration
{
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

        $imageProcessId = $this->upsertMenu('/design/image-process', [
            'pid' => $designId,
            'title' => '图片处理',
            'icon' => 'el-icon-picture-outline-round',
            'component' => '/design/image-process',
            'target' => '_self',
            'permission' => 'design:imageProcess:view',
            'type' => 0,
            'status' => 1,
            'hide' => 0,
            'note' => '',
            'sort' => 10,
        ]);

        $this->grantToAdminRoles([$designId, $imageProcessId]);
    }

    public function down(): void
    {
        $ids = DB::table('menus')
            ->whereIn('path', ['/design', '/design/image-process'])
            ->pluck('id')
            ->all();

        if (!$ids) {
            return;
        }

        DB::table('role_menu')->whereIn('menu_id', $ids)->delete();
        DB::table('menus')->whereIn('id', $ids)->delete();
    }

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
