<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('role_menu')->delete();

        $roleIds = DB::table('roles')
            ->whereIn('code', ['super', 'admin'])
            ->pluck('id')
            ->all();

        $menuIds = DB::table('menus')->pluck('id')->all();

        if (!$roleIds || !$menuIds) {
            return;
        }

        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($menuIds as $menuId) {
                $rows[] = [
                    'role_id' => $roleId,
                    'menu_id' => $menuId,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('role_menu')->insert($chunk);
        }
    }
}
