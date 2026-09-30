<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkPlatform;
use Illuminate\Validation\Rule;

class WorkPlatformRequest extends FormRequest
{
    /**
     * 获取工作平台接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/30
     */
    public function rules(): array
    {
        return match ($this->actionMethod()) {
            'index' => array_merge($this->paginationRules(), [
                'name' => ['nullable', 'string', 'max:50'],
                'category' => ['nullable', 'string', Rule::in(WorkPlatform::CATEGORIES)],
                'status' => ['nullable', 'integer', 'in:0,1'],
                'filter' => ['nullable', 'array'],
                'filter.name' => ['nullable', 'string', 'max:50'],
                'filter.category' => ['nullable', 'string', Rule::in(WorkPlatform::CATEGORIES)],
                'filter.status' => ['nullable', 'integer', 'in:0,1'],
            ]),
            'list' => [
                'status' => ['nullable', 'integer', 'in:0,1'],
            ],
            'add', 'edit' => [
                'name' => ['required', 'string', 'max:50'],
                'category' => ['required', 'string', Rule::in(WorkPlatform::CATEGORIES)],
                'status' => ['nullable', 'integer', 'in:0,1'],
                'sort' => ['nullable', 'integer'],
            ],
            'reorder' => [
                'order' => ['required_without:list', 'array'],
                'order.*.id' => ['required_with:order', 'integer', 'min:1'],
                'order.*.sort' => ['required_with:order', 'integer'],
                'list' => ['required_without:order', 'array'],
                'list.*.id' => ['required_with:list', 'integer', 'min:1'],
                'list.*.sort' => ['required_with:list', 'integer'],
            ],
            default => [],
        };
    }

    /**
     * 获取工作平台字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/30
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'name' => '平台名称',
            'category' => '平台大类',
            'status' => '状态',
            'sort' => '排序',
            'order' => '排序数据',
            'list' => '排序数据',
        ]);
    }
}
