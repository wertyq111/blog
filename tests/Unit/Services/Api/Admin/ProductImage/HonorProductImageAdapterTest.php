<?php

use App\Services\Api\Admin\ProductImage\Adapters\HonorProductImageAdapter;
use Tests\TestCase;

uses(TestCase::class);

it('解析多颜色 SKU 并保留轮播顺序', function () {
    $html = file_get_contents(base_path('tests/Fixtures/product-image/honor-two-variants.html'));
    $adapter = app(HonorProductImageAdapter::class);

    $result = $adapter->extract('https://www.honor.com/cn/shop/product/90001.html', $html);

    expect($result['platform'])
        ->toEqual(['code' => 'honor', 'name' => '荣耀商城'])
        ->and($result['product']['id'])
        ->toBe('90001')
        ->and($result['product']['title'])
        ->toBe('荣耀Earbuds耳夹式耳机Pro')
        ->and($result['variants'])
        ->toHaveCount(2)
        ->and($result['variants'][0]['attributes'])
        ->toEqual(['颜色' => '棱镜黑'])
        ->and(array_column($result['variants'][0]['images'], 'role'))
        ->toEqual(['poster', 'cover', 'gallery', 'gallery'])
        ->and($result['variants'][1]['attributes'])
        ->toEqual(['颜色' => '玫瑰金'])
        ->and(array_column($result['variants'][1]['images'], 'role'))
        ->toEqual(['poster', 'cover', 'gallery']);
});

it('直接使用页面中的原图路径而不推导尺寸前缀', function () {
    $html = file_get_contents(base_path('tests/Fixtures/product-image/honor-two-variants.html'));
    $adapter = app(HonorProductImageAdapter::class);

    $result = $adapter->extract('https://www.honor.com/cn/shop/product/90001.html', $html);
    $urls = array_column($result['variants'][0]['images'], 'url');
    $thumbnailUrls = array_column($result['variants'][0]['images'], 'thumbnailUrl');

    expect($urls[0])
        ->toBe('https://hshop.honorfile.com/pimages/display_auto/SKU-BLACK/BLACK-POSTER.jpg')
        ->and($urls[1])
        ->toBe('https://hshop.honorfile.com/pimages/product/BLACK-GBOM/BLACK-COVERmp.png')
        ->and($urls[2])
        ->toBe('https://hshop.honorfile.com/pimages/product/BLACK-GBOM/group/BLACK-GALLERY-1mp.png')
        ->and($thumbnailUrls[2])
        ->toBe('https://hshop.honorfile.com/pimages/product/BLACK-GBOM/group/800_800_BLACK-GALLERY-1mp.png')
        ->and(implode('\n', $urls))
        ->not->toContain('78_78_')
        ->not->toContain('800_800_');
});

it('按原图优先并为历史商品提供 800 尺寸候选', function () {
    $adapter = app(HonorProductImageAdapter::class);
    $url = 'https://hshop.honorfile.com/pimages/product/6936520803927/group/IMAGE.png';

    expect($adapter->imageCandidates($url))->toEqual([
        $url,
        'https://hshop.honorfile.com/pimages/product/6936520803927/group/800_800_IMAGE.png',
    ]);
});
