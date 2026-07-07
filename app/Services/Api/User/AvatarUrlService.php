<?php

namespace App\Services\Api\User;

use RuntimeException;

class AvatarUrlService
{
    /**
     * 将本地头像相对路径转换为公开访问地址。
     *
     * @param string $path
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    public function make(string $path): string
    {
        if ($path === '' || ! str_starts_with($path, '/uploads/avatars/')) {
            return $path;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('APP_URL 未配置');
        }

        return $baseUrl.$path;
    }
}
