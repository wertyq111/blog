<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RenameMemberLevelMenuComponent extends Migration
{
    /**
     * 会员等级菜单的组件路径改为 kebab-case。
     *
     * 前端页面目录由 views/member/memberlevel 改名为 views/member/member-level
     * （blog-ui-vue3 与 blog-ui 两套前端都按 component 值动态解析组件），
     * 故同步更新菜单表的 component；path 与 permission 保持不变，
     * 避免影响既有 URL 与权限码。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/1
     */
    public function up(): void
    {
        DB::table('menus')
            ->where('component', '/member/memberlevel')
            ->update(['component' => '/member/member-level']);
    }

    /**
     * 回滚组件路径。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/1
     */
    public function down(): void
    {
        DB::table('menus')
            ->where('component', '/member/member-level')
            ->update(['component' => '/member/memberlevel']);
    }
}
