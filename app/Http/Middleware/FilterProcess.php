<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FilterProcess
{
    public function handle(Request $request, Closure $next, $modelClass)
    {
        $model = new $modelClass();
        // 判断类中是否存在获取请求过滤字段数组的方法, 存在就进行过滤字段处理
        if(method_exists($model, 'getRequestFilters') && $model->getRequestFilters() != null) {
            $filters = $request->request->get('filter') ?: [];
            foreach($model->getRequestFilters() as $key => $value) {
                // 将下划线格式的数据存入数组中中
                if($request->get($key) !== null) {
                    $filters[$value['column']] = $request->get($key);
                }
            }

            $request->query->set('filter', $filters);
            //dd(QueryBuilderRequest::fromRequest($request)->filters());
        }


        return $next($request);
    }
}
