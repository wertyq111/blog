<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;

class FormRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
