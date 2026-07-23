<?php

namespace App\Services\Api\Admin\ProductImage\Adapters;

use App\Exceptions\ProductImageExtractorException;
use App\Services\Api\Admin\ProductImage\Contracts\ProductImagePlatformAdapter;

class HonorProductImageAdapter implements ProductImagePlatformAdapter
{
    /**
     * 获取平台编码。
     *
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function code(): string
    {
        return 'honor';
    }

    /**
     * 获取平台名称。
     *
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function name(): string
    {
        return (string) config('product-image-extractor.platforms.honor.name');
    }

    /**
     * 判断是否支持指定商品地址。
     *
     * @param string $url
     * @return bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function supports(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $pathPattern = (string) config('product-image-extractor.platforms.honor.page_path_pattern');

        return $scheme === 'https'
            && in_array($host, $this->pageHosts(), true)
            && preg_match($pathPattern, $path) === 1;
    }

    /**
     * 获取允许访问的商品页域名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function pageHosts(): array
    {
        return config('product-image-extractor.platforms.honor.page_hosts');
    }

    /**
     * 获取允许访问的图片域名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function imageHosts(): array
    {
        return config('product-image-extractor.platforms.honor.image_hosts');
    }

    /**
     * 校验商品页路径。
     *
     * @param string $url
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function validatePageUrl(string $url): void
    {
        if (!$this->supports($url)) {
            throw new ProductImageExtractorException('请输入有效的荣耀商城商品地址');
        }
    }

    /**
     * 校验商品图片路径。
     *
     * @param string $url
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function validateImageUrl(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            throw new ProductImageExtractorException('荣耀图片地址无效');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ($scheme !== 'https'
            || !in_array($host, $this->imageHosts(), true)
            || preg_match('#^/pimages/(?:product|display_auto)/[A-Za-z0-9._/-]+$#', $path) !== 1
            || str_contains($path, '..')) {
            throw new ProductImageExtractorException('荣耀图片地址无效');
        }
    }

    /**
     * 生成荣耀图片候选地址。
     *
     * 新商品通常保留无尺寸前缀原图，历史商品可能只保留 800×800 版本。
     *
     * @param string $url
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function imageCandidates(string $url): array
    {
        $this->validateImageUrl($url);
        $parts = parse_url($url);
        $path = (string) ($parts['path'] ?? '');
        $directory = rtrim(dirname($path), '/');
        $fileName = basename($path);
        $fallbackUrl = sprintf(
            'https://%s%s/800_800_%s',
            (string) ($parts['host'] ?? ''),
            $directory,
            $fileName,
        );
        $this->validateImageUrl($fallbackUrl);

        return array_values(array_unique([$url, $fallbackUrl]));
    }

    /**
     * 解析商品页图片数据。
     *
     * @param string $sourceUrl
     * @param string $html
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function extract(string $sourceUrl, string $html): array
    {
        $decodedHtml = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $productId = $this->requiredMatch('/ec\.product\.id\s*=\s*["\'](\d+)["\']\s*;/', $decodedHtml, '未找到商品 ID');
        $productName = $this->requiredMatch('/ec\.product\.name\s*=\s*(["\'])(.*?)\1\s*;/s', $decodedHtml, '未找到商品名称', 2);
        $attributes = $this->extractSkuAttributes($decodedHtml);
        $posters = $this->extractPosters($decodedHtml);
        $variants = $this->extractVariants($decodedHtml, $attributes, $posters);

        if (empty($variants)) {
            throw new ProductImageExtractorException('荣耀商品页结构已变更，未找到 SKU 图片');
        }

        return [
            'platform' => [
                'code' => $this->code(),
                'name' => $this->name(),
            ],
            'product' => [
                'id' => $productId,
                'title' => trim($productName),
                'sourceUrl' => $sourceUrl,
            ],
            'variants' => array_values($variants),
        ];
    }

    /**
     * 解析 SKU 属性。
     *
     * @param string $html
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function extractSkuAttributes(string $html): array
    {
        preg_match_all('/ec\.product\.setSkuAttrValueLst\([^,]+,\s*\{(.*?)\}\s*\)/s', $html, $matches);
        $attributes = [];

        foreach ($matches[1] as $object) {
            $skuId = $this->objectProperty($object, 'skuId');
            $attributeName = $this->objectProperty($object, 'attrName');
            $attributeValue = $this->objectProperty($object, 'attrValue');
            if ($skuId === null || $attributeName === null || $attributeValue === null) {
                throw new ProductImageExtractorException('荣耀 SKU 属性结构无效');
            }

            $attributes[$skuId][$attributeName] = $attributeValue;
        }

        return $attributes;
    }

    /**
     * 解析 SKU 活动首图。
     *
     * @param string $html
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function extractPosters(string $html): array
    {
        preg_match_all('/prdPosters\.sbom\.push\(\{(.*?)\}\s*\)/s', $html, $matches);
        $posters = [];

        foreach ($matches[1] as $object) {
            $skuCode = $this->objectProperty($object, 'sbomCode');
            $name = $this->objectProperty($object, 'name');
            $path = $this->objectProperty($object, 'path');
            if ($skuCode === null || $name === null || $path === null) {
                throw new ProductImageExtractorException('荣耀活动首图结构无效');
            }

            $posters[$skuCode] = [
                'url' => $this->buildImageUrl($path, $name),
                'thumbnailUrl' => $this->buildThumbnailUrl($path, $name),
                'fileName' => $name,
            ];
        }

        return $posters;
    }

    /**
     * 解析商品 SKU 及轮播图。
     *
     * @param string $html
     * @param array $attributes
     * @param array $posters
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function extractVariants(string $html, array $attributes, array $posters): array
    {
        preg_match_all('/_groupPhotoList\s*=\s*\[\]\s*;(?<body>.*?)ec\.product\.setSku\s*\(\s*["\'](?<skuId>\d+)["\']\s*,\s*\{(?<sku>.*?)\}\s*\)\s*;/s', $html, $matches, PREG_SET_ORDER);
        $variants = [];

        foreach ($matches as $match) {
            $skuId = $match['skuId'];
            $skuCode = $this->objectProperty($match['sku'], 'code');
            $variantName = $this->objectProperty($match['sku'], 'name');
            $coverName = $this->objectProperty($match['sku'], 'photoName');
            $coverPath = $this->objectProperty($match['sku'], 'photoPath');
            if ($skuCode === null || $variantName === null || $coverName === null || $coverPath === null) {
                throw new ProductImageExtractorException('荣耀 SKU 图片结构无效');
            }

            $images = [];
            if (isset($posters[$skuCode])) {
                $images[] = $this->imageData(
                    $posters[$skuCode]['url'],
                    $posters[$skuCode]['thumbnailUrl'],
                    $posters[$skuCode]['fileName'],
                    'poster',
                    count($images) + 1,
                );
            }

            $images[] = $this->imageData(
                $this->buildImageUrl($coverPath, $coverName),
                $this->buildThumbnailUrl($coverPath, $coverName),
                $coverName,
                'cover',
                count($images) + 1,
            );

            preg_match_all('/_groupPhotoList\.push\s*\(\s*\{(.*?)\}\s*\)\s*;/s', $match['body'], $groupMatches);
            foreach ($groupMatches[1] as $groupObject) {
                $groupName = $this->objectProperty($groupObject, 'name');
                $groupPath = $this->objectProperty($groupObject, 'path');
                if ($groupName === null || $groupPath === null) {
                    throw new ProductImageExtractorException('荣耀轮播图结构无效');
                }

                $images[] = $this->imageData(
                    $this->buildImageUrl($groupPath, $groupName),
                    $this->buildThumbnailUrl($groupPath, $groupName),
                    $groupName,
                    'gallery',
                    count($images) + 1,
                );
            }

            $variants[$skuId] = [
                'id' => $skuId,
                'skuId' => $skuId,
                'skuCode' => $skuCode,
                'name' => $variantName,
                'attributes' => $attributes[$skuId] ?? [],
                'images' => $images,
            ];
        }

        return $variants;
    }

    /**
     * 创建图片返回数据。
     *
     * @param string $url
     * @param string $thumbnailUrl
     * @param string $fileName
     * @param string $role
     * @param int $index
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function imageData(
        string $url,
        string $thumbnailUrl,
        string $fileName,
        string $role,
        int $index,
    ): array
    {
        return [
            'index' => $index,
            'role' => $role,
            'url' => $url,
            'thumbnailUrl' => $thumbnailUrl,
            'fileName' => $fileName,
        ];
    }

    /**
     * 组装荣耀 CDN 缩略图地址。
     *
     * @param string $path
     * @param string $name
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function buildThumbnailUrl(string $path, string $name): string
    {
        return $this->buildImageUrl($path, '800_800_' . $name);
    }

    /**
     * 组装荣耀 CDN 原图地址。
     *
     * @param string $path
     * @param string $name
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function buildImageUrl(string $path, string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
            throw new ProductImageExtractorException('荣耀图片文件名无效');
        }

        $normalizedPath = '/' . trim($path, '/') . '/';
        if (!preg_match('#^/(?:product|display_auto)/[A-Za-z0-9._/-]+/$#', $normalizedPath)
            || str_contains($normalizedPath, '..')) {
            throw new ProductImageExtractorException('荣耀图片路径无效');
        }

        $baseUrl = rtrim((string) config('product-image-extractor.platforms.honor.image_base_url'), '/');

        return $baseUrl . $normalizedPath . $name;
    }

    /**
     * 读取 JavaScript 对象的字符串属性。
     *
     * @param string $object
     * @param string $property
     * @return string|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function objectProperty(string $object, string $property): ?string
    {
        $pattern = '/(?:^|[,\s])' . preg_quote($property, '/') . '\s*:\s*(["\'])(.*?)\1/s';
        if (preg_match($pattern, $object, $match) !== 1) {
            return null;
        }

        return trim($match[2]);
    }

    /**
     * 读取必须存在的页面字段。
     *
     * @param string $pattern
     * @param string $subject
     * @param string $message
     * @param int $group
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function requiredMatch(string $pattern, string $subject, string $message, int $group = 1): string
    {
        if (preg_match($pattern, $subject, $match) !== 1 || !isset($match[$group])) {
            throw new ProductImageExtractorException($message);
        }

        return $match[$group];
    }
}
