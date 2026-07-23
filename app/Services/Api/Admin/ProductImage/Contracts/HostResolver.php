<?php

namespace App\Services\Api\Admin\ProductImage\Contracts;

interface HostResolver
{
    /**
     * 解析域名对应的全部 IP 地址。
     *
     * @param string $host
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function resolve(string $host): array;
}
