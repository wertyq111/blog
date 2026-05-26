<?php

namespace App\Http\Requests\Api\Admin;


use App\Http\Requests\Api\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{

    /**
     * 获取菜单接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        $menu = $this->route('menu');

        return match ($this->actionMethod()) {
            'index' => [
                'title' => ['nullable', 'string', 'max:255'],
                'filter' => ['nullable', 'array'],
                'filter.title' => ['nullable', 'string', 'max:255'],
            ],
            'add' => array_merge($this->menuRules(true, $menu?->id), [
                'title' => [
                    'required',
                    'between:1,25',
                    Rule::unique('menus')->where(function ($query) {
                        $query->where('deleted_at', 0);
                    }),
                ],
            ]),
            'edit' => $this->menuRules(false, $menu?->id),
            default => [],
        };
    }

    /**
     * 获取菜单保存字段规则。
     *
     * @param bool $isCreate
     * @param int|null $ignoreId
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function menuRules(bool $isCreate, ?int $ignoreId): array
    {
        $required = $isCreate ? 'required' : 'sometimes';

        return [
            'pid' => ['nullable', 'integer', 'min:0'],
            'title' => [
                $required,
                'between:1,25',
                Rule::unique('menus')->where(function ($query) {
                    $query->where('deleted_at', 0);
                })->ignore($ignoreId),
            ],
            'icon' => ['nullable', 'string', 'max:255'],
            'path' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'component' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'integer', 'in:0,1'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'hide' => ['nullable', 'integer', 'in:0,1'],
            'note' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer'],
            'checked_list' => ['nullable', 'array'],
            'checked_list.*' => ['integer'],
        ];
    }

    /**
     * 获取菜单字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return [
            'pid' => '父级菜单',
            'title' => '菜单名称',
            'icon' => '图标',
            'path' => '路由地址',
            'component' => '组件路径',
            'target' => '打开方式',
            'permission' => '权限标识',
            'type' => '菜单类型',
            'status' => '状态',
            'hide' => '显示状态',
            'note' => '备注',
            'sort' => '排序',
            'checked_list' => '权限按钮',
        ];
    }
}
