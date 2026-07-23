<?php

use App\Exceptions\ProductImageExtractorException;
use App\Services\Api\Admin\ProductImage\Adapters\HonorProductImageAdapter;
use App\Services\Api\Admin\ProductImage\Contracts\HostResolver;
use App\Services\Api\Admin\ProductImage\SafeRemoteFetcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('拒绝解析到私有地址的白名单域名', function () {
    $resolver = new class implements HostResolver {
        public function resolve(string $host): array
        {
            return ['127.0.0.1'];
        }
    };
    $fetcher = new SafeRemoteFetcher(app(Factory::class), $resolver);
    $adapter = app(HonorProductImageAdapter::class);

    expect(fn () => $fetcher->fetchPage($adapter, 'https://www.honor.com/cn/shop/product/90001.html'))
        ->toThrow(ProductImageExtractorException::class, '远程资源域名解析到非公网地址');
});

it('拒绝白名单以外的商品域名', function () {
    $adapter = app(HonorProductImageAdapter::class);

    expect(fn () => $adapter->validatePageUrl('https://www.honor.com.example.test/cn/shop/product/90001.html'))
        ->toThrow(ProductImageExtractorException::class, '请输入有效的荣耀商城商品地址');
});

it('逐次校验重定向地址并拒绝跳出白名单', function () {
    $resolver = new class implements HostResolver {
        public function resolve(string $host): array
        {
            return ['1.1.1.1'];
        }
    };
    Http::fake([
        'https://www.honor.com/*' => Http::response('', 302, [
            'Location' => 'https://example.test/cn/shop/product/90001.html',
        ]),
    ]);
    $fetcher = new SafeRemoteFetcher(app(Factory::class), $resolver);
    $adapter = app(HonorProductImageAdapter::class);

    expect(fn () => $fetcher->fetchPage($adapter, 'https://www.honor.com/cn/shop/product/90001.html'))
        ->toThrow(ProductImageExtractorException::class, '请输入有效的荣耀商城商品地址');
});

it('仅在原图明确返回 404 时尝试平台尺寸候选', function () {
    $resolver = new class implements HostResolver {
        public function resolve(string $host): array
        {
            return ['1.1.1.1'];
        }
    };
    $originalUrl = 'https://hshop.honorfile.com/pimages/product/10001/IMAGE.png';
    $fallbackUrl = 'https://hshop.honorfile.com/pimages/product/10001/800_800_IMAGE.png';
    Http::fake([
        $originalUrl => Http::response('', 404, ['Content-Type' => 'text/html']),
        $fallbackUrl => Http::response('', 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => '123',
        ]),
    ]);
    $fetcher = new SafeRemoteFetcher(app(Factory::class), $resolver);
    $adapter = app(HonorProductImageAdapter::class);

    expect($fetcher->probeFirstAvailableImage($adapter, [$originalUrl, $fallbackUrl]))
        ->toEqual([
            'url' => $fallbackUrl,
            'mimeType' => 'image/png',
            'extension' => 'png',
            'bytes' => 123,
        ]);
});
