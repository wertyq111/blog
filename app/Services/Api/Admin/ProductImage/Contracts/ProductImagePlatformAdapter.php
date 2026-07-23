<?php

namespace App\Services\Api\Admin\ProductImage\Contracts;

interface ProductImagePlatformAdapter
{
    /**
     * 获取平台编码。
     *
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function code(): string;

    /**
     * 获取平台名称。
     *
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function name(): string;

    /**
     * 判断是否支持指定商品地址。
     *
     * @param string $url
     * @return bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function supports(string $url): bool;

    /**
     * 获取允许访问的商品页域名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function pageHosts(): array;

    /**
     * 获取允许访问的图片域名。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function imageHosts(): array;

    /**
     * 校验商品页路径。
     *
     * @param string $url
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function validatePageUrl(string $url): void;

    /**
     * 校验商品图片路径。
     *
     * @param string $url
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function validateImageUrl(string $url): void;

    /**
     * 按清晰度优先级生成可尝试的图片地址。
     *
     * 平台可以在页面原图不存在时提供受控的尺寸版本，服务层只会采用首个真实可访问的地址。
     *
     * @param string $url
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function imageCandidates(string $url): array;

    /**
     * 解析商品页图片数据。
     *
     * @param string $sourceUrl
     * @param string $html
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function extract(string $sourceUrl, string $html): array;
}
