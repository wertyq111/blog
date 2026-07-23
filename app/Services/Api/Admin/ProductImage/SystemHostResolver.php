<?php

namespace App\Services\Api\Admin\ProductImage;

use App\Services\Api\Admin\ProductImage\Contracts\HostResolver;

class SystemHostResolver implements HostResolver
{
    /**
     * 解析域名对应的全部 IP 地址。
     *
     * @param string $host
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function resolve(string $host): array
    {
        return $this->resolveHost($host, 0, []);
    }

    /**
     * 递归解析 CNAME 链上的 A 和 AAAA 记录。
     *
     * @param string $host
     * @param int $depth
     * @param array $visited
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    private function resolveHost(string $host, int $depth, array $visited): array
    {
        if ($depth > 5 || isset($visited[$host])) {
            return [];
        }

        $visited[$host] = true;
        $records = dns_get_record($host, DNS_A | DNS_AAAA | DNS_CNAME);
        if ($records === false) {
            return [];
        }

        $addresses = [];
        $canonicalHosts = [];
        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $addresses[] = $record['ip'];
            }
            if (isset($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
            if (($record['type'] ?? null) === 'CNAME' && isset($record['target'])) {
                $canonicalHosts[] = rtrim(strtolower($record['target']), '.');
            }
        }

        foreach (array_unique($canonicalHosts) as $canonicalHost) {
            $addresses = array_merge($addresses, $this->resolveHost($canonicalHost, $depth + 1, $visited));
        }

        return array_values(array_unique($addresses));
    }
}
