<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

function route_class()
{
    return str_replace('.', '-', Route::currentRouteName());
}

if(!function_exists('getHttpStatus')) {
    function getHttpStatus()
    {
        $objClass = new \ReflectionClass(\Symfony\Component\HttpFoundation\Response::class);
        // 此处获取类中定义的全部常量 返回的是 [key=>value,...] 的数组,key是常量名,value是常量值
        return array_values($objClass->getConstants());
    }
}

