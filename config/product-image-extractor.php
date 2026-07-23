<?php

use App\Services\Api\Admin\ProductImage\Adapters\HonorProductImageAdapter;

return [
    'adapters' => [
        HonorProductImageAdapter::class,
    ],

    'limits' => [
        'page_bytes' => 5 * 1024 * 1024,
        'image_bytes' => 30 * 1024 * 1024,
        'zip_bytes' => 500 * 1024 * 1024,
        'images_per_zip' => 50,
        'redirects' => 3,
        'connect_timeout' => 5,
        'request_timeout' => 20,
    ],

    'platforms' => [
        'honor' => [
            'name' => '荣耀商城',
            'page_hosts' => [
                'www.honor.com',
            ],
            'image_hosts' => [
                'hshop.honorfile.com',
            ],
            'page_path_pattern' => '#^/cn/shop/product/\d+\.html$#',
            'image_base_url' => 'https://hshop.honorfile.com/pimages',
        ],
    ],
];
