<?php

namespace App\Services\Api\Admin\PlatformScript\Support;

use phpseclib3\Net\SSH2;

/**
 * 通用 SSH 执行器：在远端主机执行命令并返回输出。
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/7/23
 */
class SshRunner
{
    /**
     * 在远端执行命令。
     *
     * @param array $connection host/port/username/password/timeout
     * @param string $command
     * @return array{output: string, exit_status: int|null, timed_out: bool}
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function run(array $connection, string $command): array
    {
        if (empty($connection['password'])) {
            throw new \RuntimeException('SSH 密码未配置，请在 .env 设置 SINOLOANS_SSH_PASSWORD');
        }

        $timeout = (int) ($connection['timeout'] ?? 120);

        $ssh = new SSH2($connection['host'], (int) $connection['port'], $timeout);
        $ssh->setTimeout($timeout);

        if (!$ssh->login($connection['username'], $connection['password'])) {
            throw new \RuntimeException('SSH 登录失败：' . $connection['username'] . '@' . $connection['host']);
        }

        $output = $ssh->exec($command);

        return [
            'output' => is_string($output) ? $output : '',
            'exit_status' => $ssh->getExitStatus() === false ? null : $ssh->getExitStatus(),
            // 命令未在超时时限内结束时为 true（远端仍可能在跑，输出多半没读回）
            'timed_out' => $ssh->isTimeout(),
        ];
    }
}
