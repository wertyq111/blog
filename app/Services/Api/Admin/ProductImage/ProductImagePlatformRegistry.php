<?php

namespace App\Services\Api\Admin\ProductImage;

use App\Exceptions\ProductImageExtractorException;
use App\Services\Api\Admin\ProductImage\Contracts\ProductImagePlatformAdapter;
use Illuminate\Contracts\Container\Container;

class ProductImagePlatformRegistry
{
    /**
     * 初始化商品图片平台注册表。
     *
     * @param Container $container
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * 获取已注册平台列表。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function platforms(): array
    {
        return array_map(
            static fn (ProductImagePlatformAdapter $adapter) => [
                'code' => $adapter->code(),
                'name' => $adapter->name(),
                'domains' => $adapter->pageHosts(),
            ],
            $this->adapters(),
        );
    }

    /**
     * 根据商品地址选择平台适配器。
     *
     * @param string $platform
     * @param string $url
     * @return ProductImagePlatformAdapter
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function adapterFor(string $platform, string $url): ProductImagePlatformAdapter
    {
        foreach ($this->adapters() as $adapter) {
            if ($adapter->code() !== $platform) {
                continue;
            }

            if (!$adapter->supports($url)) {
                throw new ProductImageExtractorException('商品地址不属于选中的商品平台');
            }

            return $adapter;
        }

        throw new ProductImageExtractorException('暂不支持该商品平台');
    }

    /**
     * 实例化全部平台适配器。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function adapters(): array
    {
        $adapterClasses = config('product-image-extractor.adapters');
        if (!is_array($adapterClasses)) {
            throw new \LogicException('商品图片平台适配器配置无效');
        }

        return array_map(function (string $adapterClass): ProductImagePlatformAdapter {
            $adapter = $this->container->make($adapterClass);
            if (!$adapter instanceof ProductImagePlatformAdapter) {
                throw new \LogicException("{$adapterClass} 未实现商品图片平台适配器接口");
            }

            return $adapter;
        }, $adapterClasses);
    }
}
