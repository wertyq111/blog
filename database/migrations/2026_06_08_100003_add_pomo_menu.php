<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPomoMenu extends Migration
{
    public function up(): void
    {
        // 复用已有「个人中心」顶级菜单；不存在则创建
        $parent = DB::table('menus')
            ->where('title', '个人中心')
            ->where('type', 0)
            ->where('pid', 0)
            ->first();

        $parentId = $parent
            ? (int) $parent->id
            : $this->upsertMenu('/profile-center', [
                'pid' => 0,
                'title' => '个人中心',
                'icon' => 'el-icon-user',
                'component' => '',
                'target' => '_self',
                'permission' => '',
                'type' => 0,
                'status' => 1,
                'hide' => 0,
                'note' => '',
                'sort' => 90,
            ]);

        $pomoId = $this->upsertMenu('/profile-center/pomo', [
            'pid' => $parentId,
            'title' => '专注番茄',
            'icon' => 'el-icon-alarm-clock',
            'component' => '/pomo/index',
            'target' => '_self',
            'permission' => 'pomo:view',
            'type' => 0,
            'status' => 1,
            'hide' => 0,
            'note' => '番茄钟 + 提醒清单',
            'sort' => 10,
        ]);

        $this->grantToAdminRoles([$parentId, $pomoId]);
    }

    public function down(): void
    {
        $ids = DB::table('menus')
            ->whereIn('path', ['/profile-center/pomo'])
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
