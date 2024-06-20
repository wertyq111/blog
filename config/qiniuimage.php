<?php

return [
    /*
     * 图片路径
     */
    'paths' => [
        'home' => [
            'dir' => 'homes/%member%/',
            'params' => ['member']
        ],
        'area' => [
            'dir' => 'homes/%member%/%pid%/',
            'params' => ['member', 'pid']
        ]
    ],
];
