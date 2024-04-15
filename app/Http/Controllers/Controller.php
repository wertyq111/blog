<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Spatie\QueryBuilder\QueryBuilder;

class Controller extends BaseController
{
    // 默认分页条数
    const PER_PAGE = 10;

    use AuthorizesRequests, ValidatesRequests;

    /**
     * 使用反射方法输出
     *
     * @param $data
     * @param $params
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/11 09:26
     */
    public function resource($data, $params = null)
    {
        $resource = new \ReflectionClass("App\Http\Resources\BaseResource");

        // 显示时间
        if(isset($params['time'])) {
            $method = $resource->getMethod('showTime');
            $method->invoke($resource);
        }

        // 分页数组
        if(isset($params['collection'])) {
            $method = $resource->getMethod('collection');

            return $method->invoke($resource, $data);
        }

        return $resource->newInstance($data);
    }

    /**
     * 查询
     *
     * @param $model
     * @param $isPaginate
     * @param $config
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|mixed[]|QueryBuilder[]
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/11 09:34
     */
    public function queryBuilder($model, $isPaginate = false, $config = [])
    {
        $queryBuilder = QueryBuilder::for($model);

        if(isset($config['includes'])) {
            $queryBuilder->allowedIncludes($config['includes']);
        }

        if(isset($config['allowedFilters'])) {
            $queryBuilder->allowedFilters($config['allowedFilters']);
        }

        // 如果存在 member 值,添加新的查询条件
        if(isset($config['type'])) {
            //$queryBuilder->allowedFilters("downloads.member_id", 1);
        }

        if(isset($config['orderBy'])) {
            foreach($config['orderBy'] as $orderBy) {
                $queryBuilder->orderBy(key($orderBy), $orderBy[key($orderBy)]);
            }
        } else {
            $queryBuilder->orderBy('id', 'desc');
        }

        if($isPaginate) {
            return $queryBuilder->paginate($config['perPage'] ?? self::PER_PAGE);
        } elseif (isset($config['toArray'])) {
            return $queryBuilder->get()->toArray();
        } else {
            return $queryBuilder->get();
        }
    }
}
