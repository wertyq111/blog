<?php

namespace App\Services\Api\Admin\ProductImage;

use App\Exceptions\ProductImageExtractorException;
use App\Services\Api\Admin\ProductImage\Contracts\ProductImagePlatformAdapter;

class ProductImageExtractorService
{
    /**
     * 初始化商品图片提取服务。
     *
     * @param ProductImagePlatformRegistry $registry
     * @param SafeRemoteFetcher $fetcher
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function __construct(
        private readonly ProductImagePlatformRegistry $registry,
        private readonly SafeRemoteFetcher $fetcher,
    ) {
    }

    /**
     * 获取支持的商品平台。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function platforms(): array
    {
        return $this->registry->platforms();
    }

    /**
     * 提取商品图片及元数据。
     *
     * @param string $platform
     * @param string $sourceUrl
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function extract(string $platform, string $sourceUrl): array
    {
        [$adapter, $result] = $this->parse($platform, $sourceUrl);

        foreach ($result['variants'] as &$variant) {
            foreach ($variant['images'] as &$image) {
                $image['id'] = $this->imageId($variant['id'], $image);
                $image = $this->resolveImage($adapter, $image);
            }
            unset($image);
        }
        unset($variant);

        return $result;
    }

    /**
     * 重新解析商品页并匹配下载图片。
     *
     * @param string $platform
     * @param string $sourceUrl
     * @param array $imageIds
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function selectForDownload(string $platform, string $sourceUrl, array $imageIds): array
    {
        $maxImages = (int) config('product-image-extractor.limits.images_per_zip');
        if (count($imageIds) > $maxImages) {
            throw new ProductImageExtractorException("单次最多下载 {$maxImages} 张图片", 413);
        }

        [$adapter, $result] = $this->parse($platform, $sourceUrl);
        $requestedIds = array_fill_keys($imageIds, true);
        $selected = [];

        foreach ($result['variants'] as $variant) {
            foreach ($variant['images'] as $image) {
                $imageId = $this->imageId($variant['id'], $image);
                if (!isset($requestedIds[$imageId])) {
                    continue;
                }

                $image['id'] = $imageId;
                $image = $this->resolveImage($adapter, $image);
                $selected[] = [
                    'variantId' => $variant['id'],
                    'variantName' => $variant['name'],
                    'image' => $image,
                ];
                unset($requestedIds[$imageId]);
            }
        }

        if (!empty($requestedIds)) {
            throw new ProductImageExtractorException('选中的图片已失效，请重新提取商品图片');
        }

        return [
            'adapter' => $adapter,
            'product' => $result['product'],
            'images' => $selected,
        ];
    }

    /**
     * 获取并解析商品页。
     *
     * @param string $platform
     * @param string $sourceUrl
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function parse(string $platform, string $sourceUrl): array
    {
        $adapter = $this->registry->adapterFor($platform, $sourceUrl);
        $adapter->validatePageUrl($sourceUrl);
        $html = $this->fetcher->fetchPage($adapter, $sourceUrl);
        $result = $adapter->extract($sourceUrl, $html);

        return [$adapter, $result];
    }

    /**
     * 生成仅能匹配当前解析结果的图片 ID。
     *
     * @param string $variantId
     * @param array $image
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function imageId(string $variantId, array $image): string
    {
        $payload = implode('|', [$variantId, $image['index'], $image['role'], $image['url']]);
        $key = config('app.key');
        if (!is_string($key) || $key === '') {
            throw new \LogicException('APP_KEY 未配置，无法生成商品图片 ID');
        }

        return hash_hmac('sha256', $payload, $key);
    }

    /**
     * 解析平台图片候选地址并附加响应元数据。
     *
     * @param ProductImagePlatformAdapter $adapter
     * @param array $image
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function resolveImage(ProductImagePlatformAdapter $adapter, array $image): array
    {
        $resolved = $this->fetcher->probeFirstAvailableImage(
            $adapter,
            $adapter->imageCandidates($image['url']),
        );

        return array_merge($image, $resolved);
    }
}
