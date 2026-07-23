<?php

namespace App\Services\Api\Admin\ProductImage;

use App\Exceptions\ProductImageExtractorException;
use App\Services\Api\Admin\ProductImage\Contracts\ProductImagePlatformAdapter;
use ZipArchive;

class ProductImageZipService
{
    /**
     * 初始化商品图片压缩服务。
     *
     * @param SafeRemoteFetcher $fetcher
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function __construct(private readonly SafeRemoteFetcher $fetcher)
    {
    }

    /**
     * 下载商品图片并创建 ZIP 文件。
     *
     * @param ProductImagePlatformAdapter $adapter
     * @param array $product
     * @param array $images
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function create(ProductImagePlatformAdapter $adapter, array $product, array $images): array
    {
        if (empty($images)) {
            throw new ProductImageExtractorException('请选择要下载的图片');
        }

        $temporaryRoot = storage_path('app/tmp/product-image-extractor');
        $this->ensureDirectory($temporaryRoot);
        $token = bin2hex(random_bytes(16));
        $imageDirectory = $temporaryRoot . '/' . $token;
        $zipPath = $temporaryRoot . '/' . $token . '.zip';
        $this->ensureDirectory($imageDirectory);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            rmdir($imageDirectory);
            throw new \RuntimeException('无法创建商品图片压缩包');
        }

        $temporaryFiles = [];
        $totalBytes = 0;
        $zipLimit = (int) config('product-image-extractor.limits.zip_bytes');
        $zipClosed = false;

        try {
            foreach ($images as $index => $selection) {
                $temporaryPath = $imageDirectory . '/' . sprintf('%03d.bin', $index + 1);
                $temporaryFiles[] = $temporaryPath;
                $metadata = $this->fetcher->downloadImage(
                    $adapter,
                    $selection['image']['url'],
                    $temporaryPath,
                );
                $totalBytes += $metadata['bytes'];
                if ($totalBytes > $zipLimit) {
                    throw new ProductImageExtractorException('压缩包图片总大小超过限制', 413);
                }

                $variantDirectory = $this->safeName($selection['variantName']) . '_' . $selection['variantId'];
                $imageName = sprintf(
                    '%02d_%s.%s',
                    $selection['image']['index'],
                    $selection['image']['role'],
                    $metadata['extension'],
                );

                if (!$zip->addFile($temporaryPath, $variantDirectory . '/' . $imageName)) {
                    throw new \RuntimeException('图片写入压缩包失败');
                }
            }

            $closeResult = $zip->close();
            $zipClosed = true;
            if (!$closeResult) {
                throw new \RuntimeException('商品图片压缩包关闭失败');
            }
        } catch (\Throwable $exception) {
            if (!$zipClosed) {
                $zip->close();
            }
            if (is_file($zipPath)) {
                unlink($zipPath);
            }
            throw $exception;
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    unlink($temporaryFile);
                }
            }
            if (is_dir($imageDirectory)) {
                rmdir($imageDirectory);
            }
        }

        return [
            'path' => $zipPath,
            'name' => 'product-images-' . $product['id'] . '.zip',
        ];
    }

    /**
     * 创建商品图片临时目录。
     *
     * @param string $directory
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('无法创建商品图片临时目录');
        }
    }

    /**
     * 生成安全的 ZIP 目录名。
     *
     * @param string $name
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function safeName(string $name): string
    {
        $safeName = preg_replace('/[^\pL\pN._-]+/u', '_', trim($name));
        if (!is_string($safeName) || $safeName === '') {
            throw new ProductImageExtractorException('商品规格名称无法用于压缩包目录');
        }

        return $safeName;
    }
}
