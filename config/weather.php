<?php

return [
    // HTTP 请求的超时时间（秒）
    'timeout' => 10.0,

    // 高德地图发送配置
    'amap' => [
        // 默认可用的发送网关
        'key' => env('WEATHER_AMAP_KEY'),

        // IP定位请求地址
        'ip_position' => 'https://restapi.amap.com/v3/ip',

        // 天气信息请求地址
        'weather_info' => 'https://restapi.amap.com/v3/weather/weatherInfo',

        // 响应参数
        'response' => [
            'infocode' => 10000
        ]
    ]
];
