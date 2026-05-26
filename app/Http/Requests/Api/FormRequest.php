<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;

class FormRequest extends BaseFormRequest
{
    /**
     * 请求校验前补齐下划线参数。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();
        $this->merge($this->transformSnake($data));
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取公共分页校验规则。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    protected function paginationRules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * 获取公共分页字段别名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    protected function paginationAttributes(): array
    {
        return [
            'page' => '页码',
            'per_page' => '每页数量',
            'pageSize' => '每页数量',
            'limit' => '每页数量',
        ];
    }

    /**
     * 解析公共分页条数。
     *
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function perPage(): int
    {
        return (int) ($this->query('per_page')
            ?? $this->query('limit')
            ?? $this->query('pageSize')
            ?? $this->input('per_page')
            ?? $this->input('limit')
            ?? $this->input('pageSize')
            ?? 10);
    }

    /**
     * 获取当前路由方法名。
     *
     * @return string|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    protected function actionMethod(): ?string
    {
        return $this->route()?->getActionMethod();
    }

    /**
     * 解析整数 ID 列表。
     *
     * @param string $key
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function integerIds(string $key = 'id'): array
    {
        $ids = $this->input($key);
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_map(static fn($id) => filter_var($id, FILTER_VALIDATE_INT), $ids);
        $ids = array_values(array_filter($ids, static fn($id) => $id !== false && $id > 0));

        if (empty($ids)) {
            throw new \InvalidArgumentException('请选择要操作的记录');
        }

        return $ids;
    }

    /**
     * 将请求参数中键值转换成下划线格式
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/6 17:11
     */
    public function getSnakeRequest()
    {
        $requestArray = $this->all();
        return $this->transformSnake($requestArray);
    }

    // 小驼峰转换成下划线
    public function transformSnake(array &$array)
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $this->transformSnake($v);
            }
            unset($array[$k]);
            $array[Str::snake($k)] = $v;
        }

        return $array;
    }

    /**
     * 返回允许过滤数组
     *
     * @param array $filters
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/14 09:14
     */
    public function generateAllowedFilters(array $filters)
    {
        $allowedFilters = [];
        $filtersArray = [];
        foreach($filters as $key => $value) {
            if(isset($value['filterType'])) {
                $allowedFilters[] = $this->getAllowedFilterType($value['filterType'], $value['column']);
            } else {
                $allowedFilters[] = $value['column'];
            }
        }

        return $allowedFilters;
    }

    public function setFilters()
    {

    }

    /**
     * 根据过滤类型返回过滤方法
     *
     * @param $type
     * @param $value
     * @return AllowedFilter|void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/14 09:12
     */
    public function getAllowedFilterType($type, $value)
    {
        switch($type) {
            case 'exact':
                return AllowedFilter::exact($value);
                break;
        }
    }
}
