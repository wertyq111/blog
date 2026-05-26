<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\FormRequest;

class ServerPathRequest extends FormRequest
{
    /**
     * 获取服务器路径接口校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function rules(): array
    {
        $method = $this->route()->getActionMethod();

        return match ($method) {
            'index' => array_merge($this->paginationRules(), [
                'code' => ['nullable', 'string', 'max:255'],
                'name' => ['nullable', 'string', 'max:255'],
                'filter' => ['nullable', 'array'],
                'filter.code' => ['nullable', 'string', 'max:255'],
                'filter.name' => ['nullable', 'string', 'max:255'],
            ]),
            'add', 'edit' => [
                'code' => ['required', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'url' => ['required', 'string', 'max:120'],
                'target' => ['required', 'string'],
                'sources' => ['required', 'array'],
                'sources.*' => ['string'],
                'sort' => ['required', 'integer'],
            ],
            'convert' => [
                'paths' => ['required', 'array'],
                'paths.*' => ['required', 'string'],
            ],
            'batchDelete' => [
                'id' => ['required'],
            ],
            default => [],
        };
    }

    /**
     * 获取服务器路径字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'code' => '项目编码',
            'name' => '项目名称',
            'url' => '网址',
            'target' => '服务器地址',
            'sources' => '来源地址',
            'sort' => '排序',
            'paths' => '待转换路径',
            'id' => '记录 ID',
        ]);
    }
}
