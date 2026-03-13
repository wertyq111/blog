<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * 默认权限节点排序。
     *
     * 这里和前端菜单编辑页的 transfer key 保持一致，避免后续再编辑时节点错位。
     */
    private const PERMISSION_SORTS = [
        'index' => 1,
        'view' => 1,
        'add' => 5,
        'edit' => 10,
        'delete' => 15,
        'detail' => 20,
        'status' => 25,
        'dall' => 30,
        'addz' => 35,
        'expand' => 40,
        'collapse' => 45,
        'export' => 50,
        'import' => 55,
        'permission' => 60,
        'resetPwd' => 65,
        'convert' => 70,
    ];

    public function run(): void
    {
        DB::table('menus')->delete();

        foreach ($this->definitions() as $definition) {
            $this->createMenu($definition);
        }
    }

    private function createMenu(array $definition, int $pid = 0): int
    {
        $menuId = DB::table('menus')->insertGetId($this->menuPayload($definition, $pid));

        foreach ($definition['permissions'] ?? [] as $permission) {
            DB::table('menus')->insert($this->permissionPayload($menuId, $permission));
        }

        foreach ($definition['children'] ?? [] as $child) {
            $this->createMenu($child, $menuId);
        }

        return $menuId;
    }

    private function menuPayload(array $definition, int $pid): array
    {
        $now = time();

        return [
            'pid' => $pid,
            'title' => $definition['title'],
            'icon' => $definition['icon'] ?? '',
            'path' => $definition['path'] ?? '',
            'component' => $definition['component'] ?? '',
            'target' => $definition['target'] ?? '_self',
            'permission' => $definition['permission'] ?? '',
            'type' => 0,
            'status' => $definition['status'] ?? 1,
            'hide' => $definition['hide'] ?? 0,
            'note' => $definition['note'] ?? '',
            'sort' => $definition['sort'] ?? 0,
            'create_user' => 0,
            'created_at' => $now,
            'update_user' => 0,
            'updated_at' => $now,
            'deleted_at' => 0,
        ];
    }

    private function permissionPayload(int $pid, array $permission): array
    {
        $now = time();

        return [
            'pid' => $pid,
            'title' => $permission['title'],
            'icon' => '',
            'path' => '',
            'component' => '',
            'target' => '_self',
            'permission' => $permission['permission'],
            'type' => 1,
            'status' => 1,
            'hide' => 1,
            'note' => '',
            'sort' => $permission['sort'],
            'create_user' => 0,
            'created_at' => $now,
            'update_user' => 0,
            'updated_at' => $now,
            'deleted_at' => 0,
        ];
    }

    private function module(
        string $title,
        string $path,
        string $icon,
        int $sort,
        string $permission,
        string $permissionTitle,
        array $actions = [],
        array $extra = []
    ): array {
        return array_merge([
            'title' => $title,
            'icon' => $icon,
            'path' => $path,
            'component' => $path,
            'target' => '_self',
            'permission' => $permission,
            'sort' => $sort,
            'permissions' => $this->permissionNodes($permissionTitle, $permission, $actions),
        ], $extra);
    }

    private function hiddenRoute(string $title, string $path, int $sort): array
    {
        return [
            'title' => $title,
            'icon' => '',
            'path' => $path,
            'component' => $path,
            'target' => '_self',
            'permission' => '',
            'sort' => $sort,
            'hide' => 1,
            'permissions' => [],
        ];
    }

    private function permissionNodes(string $moduleTitle, string $leafPermission, array $actions): array
    {
        if (!$actions) {
            return [];
        }

        $base = str_contains($leafPermission, ':')
            ? substr($leafPermission, 0, strrpos($leafPermission, ':'))
            : $leafPermission;

        $nodes = [];
        foreach ($actions as $action) {
            $nodes[] = [
                'title' => $this->permissionTitle($moduleTitle, $action),
                'permission' => "{$base}:{$action}",
                'sort' => self::PERMISSION_SORTS[$action] ?? 90,
            ];
        }

        return $nodes;
    }

    private function permissionTitle(string $moduleTitle, string $action): string
    {
        $templates = [
            'index' => '查询%s',
            'view' => '查看%s',
            'add' => '添加%s',
            'edit' => '修改%s',
            'delete' => '删除%s',
            'detail' => '查看%s详情',
            'status' => '设置%s状态',
            'dall' => '批量删除%s',
            'addz' => '添加%s子级',
            'expand' => '展开%s',
            'collapse' => '折叠%s',
            'export' => '导出%s',
            'import' => '导入%s',
            'permission' => '分配%s权限',
            'resetPwd' => '重置%s密码',
            'convert' => '执行%s转换',
        ];

        return sprintf($templates[$action] ?? '%s', $moduleTitle);
    }

    private function definitions(): array
    {
        return [
            [
                'title' => '面板主页',
                'icon' => 'el-icon-house',
                'path' => '/dashboard',
                'component' => '',
                'target' => '_self',
                'permission' => '',
                'sort' => 10,
                'children' => [
                    $this->module('工作台', '/dashboard/workplace', 'el-icon-monitor', 10, '', '工作台'),
                ],
            ],
            [
                'title' => '系统管理',
                'icon' => 'el-icon-s-management',
                'path' => '/system',
                'component' => '',
                'target' => '_self',
                'permission' => '',
                'sort' => 20,
                'children' => [
                    $this->module('用户管理', '/system/user', 'el-icon-_user-group', 10, 'sys:user:index', '用户', ['index', 'add', 'edit', 'delete', 'status', 'dall', 'resetPwd']),
                    $this->module('角色管理', '/system/role', 'el-icon-postcard', 20, 'sys:role:index', '角色', ['index', 'add', 'edit', 'delete', 'status', 'dall', 'permission']),
                    $this->module('菜单管理', '/system/menu', 'el-icon-s-operation', 30, 'sys:menu:index', '菜单', ['index', 'addz', 'edit', 'delete']),
                    $this->hiddenRoute('用户详情', '/system/user/info', 40),
                ],
            ],
            [
                'title' => '会员管理',
                'icon' => 'el-icon-user',
                'path' => '/member',
                'component' => '',
                'target' => '_self',
                'permission' => '',
                'sort' => 30,
                'children' => [
                    $this->module('会员等级', '/member/memberlevel', 'el-icon-user', 10, 'sys:memberlevel:index', '会员等级', ['index', 'add', 'edit', 'delete', 'status', 'dall']),
                    $this->module('会员管理', '/member/member', 'el-icon-_user-group', 20, 'sys:member:index', '会员', ['index', 'add', 'edit', 'delete', 'status']),
                ],
            ],
            [
                'title' => '开发管理',
                'icon' => 'el-icon-copy-document',
                'path' => '/develop',
                'component' => '',
                'target' => '_self',
                'permission' => '',
                'sort' => 40,
                'children' => [
                    $this->module('路径转换', '/develop/convert-path', 'el-icon-_surveying', 10, 'sys:convert-path:index', '路径转换', ['index', 'add', 'edit', 'delete', 'dall', 'convert']),
                    $this->module('模型初始化', '/develop/init-model', 'el-icon-_module', 20, 'sys:init-model:index', '模型初始化', ['index', 'add', 'edit', 'delete', 'dall', 'convert']),
                    $this->module('工作平台', '/develop/work-platform', 'el-icon-s-grid', 30, 'dev:workPlatform:view', '工作平台'),
                    $this->module('工作日常', '/develop/work-daily', 'el-icon-date', 40, 'dev:workDaily:view', '工作日常'),
                    $this->module('工作文档', '/develop/work-doc', 'el-icon-folder-opened', 50, 'dev:workDoc:view', '工作文档'),
                ],
            ],
            [
                'title' => '小程序管理',
                'icon' => 'el-icon-mobile-phone',
                'path' => '/mini-program',
                'component' => '',
                'target' => '_self',
                'permission' => '',
                'sort' => 50,
                'children' => [
                    $this->module('壁纸分类', '/mini-program/wallpaper-classify', 'el-icon-picture-outline-round', 10, 'sys:wallpaper-classify:index', '壁纸分类', ['index', 'add', 'edit', 'delete', 'dall']),
                    $this->module('壁纸管理', '/mini-program/wallpaper', 'el-icon-picture', 20, 'sys:wallpaper:index', '壁纸', ['index', 'add', 'edit', 'delete']),
                    $this->module('笔记分类', '/mini-program/notebook-category', 'el-icon-collection-tag', 30, 'sys:notebook-category:index', '笔记分类', ['index', 'add', 'edit', 'delete']),
                    $this->module('笔记标签', '/mini-program/notebook-label', 'el-icon-price-tag', 40, 'sys:notebook-label:index', '笔记标签', ['index', 'add', 'edit', 'delete']),
                    $this->module('笔记管理', '/mini-program/notebook', 'el-icon-notebook-2', 50, 'sys:notebook:index', '笔记', ['index', 'add', 'edit', 'delete']),
                    $this->module('相册分类', '/mini-program/photo-category', 'el-icon-folder', 60, 'sys:photo-category:index', '相册分类', ['index', 'add', 'edit', 'delete']),
                    $this->module('相册管理', '/mini-program/photo', 'el-icon-camera', 70, 'sys:photo:index', '相册', ['index', 'add', 'edit', 'delete', 'dall']),
                ],
            ],
            [
                'title' => '烟草管理',
                'icon' => 'el-icon-data-analysis',
                'path' => '/tobacco',
                'component' => '',
                'target' => '_self',
                'permission' => '',
                'sort' => 60,
                'children' => [
                    $this->module('客户数据', '/tobacco/tobacco-customer', 'el-icon-user-solid', 10, 'sys:tobacco-customer:index', '客户数据', ['index', 'import']),
                    $this->module('订货数据', '/tobacco/tobacco-order', 'el-icon-s-order', 20, 'sys:tobacco-order:index', '订货数据', ['index', 'import']),
                    $this->module('1024定点', '/tobacco/tobacco-designated', 'el-icon-position', 30, 'sys:tobacco-designated:index', '1024定点', ['index', 'import']),
                    $this->module('补供数据', '/tobacco/tobacco-supplement', 'el-icon-box', 40, 'sys:tobacco-supplement:index', '补供数据', ['index', 'import']),
                    $this->module('云烟补供', '/tobacco/tobacco-yun', 'el-icon-cloudy', 50, 'sys:tobacco-yun:index', '云烟补供', ['index', 'import']),
                    $this->module('供货限量', '/tobacco/tobacco-supply', 'el-icon-set-up', 60, 'sys:tobacco-supply:index', '供货限量', ['index', 'import']),
                    $this->module('订货检查', '/tobacco/tobacco-order-inspect', 'el-icon-view', 70, 'sys:tobacco-order-inspect:index', '订货检查'),
                ],
            ],
            [
                'title' => '个人中心',
                'icon' => 'el-icon-user-solid',
                'path' => '/user',
                'component' => '',
                'target' => '_self',
                'permission' => '',
                'sort' => 70,
                'children' => [
                    $this->module('个人资料', '/user/profile', 'el-icon-user', 10, 'sys:profile:index', '个人资料', ['index', 'edit']),
                ],
            ],
        ];
    }
}
