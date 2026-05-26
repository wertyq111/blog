<?php

namespace App\Http\Requests\Api\Admin;


use App\Http\Requests\Api\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{

    /**
     * 获取角色接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        $role = $this->route('role');

        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'code' => ['nullable', 'string', 'max:255'],
                'name' => ['nullable', 'string', 'max:255'],
                'filter' => ['nullable', 'array'],
                'filter.code' => ['nullable', 'string', 'max:255'],
                'filter.name' => ['nullable', 'string', 'max:255'],
            ]),
            'add', 'edit' => [
                'name' => [
                    'required',
                    'between:3,25',
                    Rule::unique('roles')->where(function ($query) {
                        $query->where('deleted_at', 0);
                    })->ignore($role?->id),
                ],
                'code' => ['required', 'string', 'min:3', 'max:255'],
                'sort' => ['nullable', 'integer'],
                'status' => ['nullable', 'integer', 'in:0,1'],
                'note' => ['nullable', 'string', 'max:255'],
            ],
            'status' => [
                'status' => ['required', 'integer', 'in:0,1'],
            ],
            'batchDelete' => [
                'id' => ['required'],
            ],
            'savePermissionList' => [
                'menu_id' => ['required', 'array'],
                'menu_id.*' => ['integer', 'min:1'],
            ],
            default => [],
        };
    }

    /**
     * 获取角色字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'name' => '角色名称',
            'code' => '角色编码',
            'sort' => '排序',
            'status' => '状态',
            'note' => '备注',
            'id' => '记录 ID',
            'menu_id' => '菜单权限',
        ]);
    }
}
