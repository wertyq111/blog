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
            // Symfony 6 的 InputBag::get() 不再允许直接读取数组值，这里显式按数组方式提取 filter。
            $filters = $request->query->all('filter');

            if (!is_array($filters) || $filters === []) {
                $filters = $request->request->all('filter');
            }

            if (!is_array($filters)) {
                $filters = [];
            }

            foreach($model->getRequestFilters() as $key => $value) {
                // 将下划线格式的数据存入数组中中，但不覆盖 query 中已有的 filter 值
                $col = $value['column'];
                if (!array_key_exists($col, $filters)) {
                    $v = $request->get($key);
                    if ($v !== null) {
                        $filters[$col] = $v;
                    }
                }
            }

            $request->query->set('filter', $filters);
            //dd(QueryBuilderRequest::fromRequest($request)->filters());
        }


        return $next($request);
    }
}
