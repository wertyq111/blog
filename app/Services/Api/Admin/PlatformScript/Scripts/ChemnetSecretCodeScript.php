<?php

namespace App\Services\Api\Admin\PlatformScript\Scripts;

use App\Services\Api\Admin\PlatformScript\Support\PmaClient;
use Illuminate\Validation\ValidationException;

/**
 * ChemNet 验证码手机号修改脚本。
 *
 * 连接 .secret_code 数据表，输入账号（login）查询对应的手机号（mobile），
 * 并支持更新该账号的手机号。
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/8/26
 */
class ChemnetSecretCodeScript
{
    public const KEY = 'chemnet-secret-code';

    private readonly PmaClient $pmaClient;

    /**
     * @param array $config 该脚本在 config/platform-script.php 的配置片段
     */
    public function __construct(private readonly array $config)
    {
        $this->pmaClient = new PmaClient($this->config);
    }

    /**
     * 根据账号（login）查询验证码表记录。
     *
     * @param string $login
     * @return array{found: bool, record: ?array<string, string>, list: array<int, array<string, string>>}
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    public function queryByLogin(string $login): array
    {
        $cleanLogin = trim($login);
        if ($cleanLogin === '') {
            throw ValidationException::withMessages(['login' => '请输入要查询的账号 (login)']);
        }

        $escaped = addslashes($cleanLogin);
        $sql = "SELECT id, login, mobile, code, num, status, post_time, post_ip FROM secret_code WHERE login = '{$escaped}' ORDER BY id DESC LIMIT 10";

        $rows = $this->pmaClient->query($sql);

        return [
            'found' => !empty($rows),
            'record' => $rows[0] ?? null,
            'list' => $rows,
        ];
    }

    /**
     * 修改指定账号（login）的手机号（mobile）。
     *
     * @param string $login
     * @param string $newMobile
     * @return array{login: string, old_mobile: string, new_mobile: string, affected_message: string, current_record: ?array<string, string>}
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    public function updateMobile(string $login, string $newMobile): array
    {
        $cleanLogin = trim($login);
        $cleanMobile = trim($newMobile);

        if ($cleanLogin === '') {
            throw ValidationException::withMessages(['login' => '账号 (login) 不能为空']);
        }
        if ($cleanMobile === '' || !preg_match('/^1\d{10}$/', $cleanMobile)) {
            throw ValidationException::withMessages(['mobile' => '请输入有效的 11 位手机号码']);
        }

        // 1. 查询当前记录
        $before = $this->queryByLogin($cleanLogin);
        if (empty($before['found']) || empty($before['record'])) {
            throw ValidationException::withMessages(['login' => "未在 secret_code 表中找到账号 [{$cleanLogin}] 的记录"]);
        }

        $oldMobile = $before['record']['mobile'] ?? '';

        // 2. 执行更新
        $escapedLogin = addslashes($cleanLogin);
        $escapedMobile = addslashes($cleanMobile);
        $updateSql = "UPDATE secret_code SET mobile = '{$escapedMobile}' WHERE login = '{$escapedLogin}'";

        $execResult = $this->pmaClient->execute($updateSql);

        // 3. 再次查询以获取最新记录
        $after = $this->queryByLogin($cleanLogin);

        return [
            'login' => $cleanLogin,
            'old_mobile' => $oldMobile,
            'new_mobile' => $cleanMobile,
            'affected_message' => $execResult['message'] ?? '',
            'current_record' => $after['record'] ?? null,
        ];
    }

    /**
     * 根据历史最大 ordrNo 计算下一个（无历史时以种子 +1）。
     *
     * @param string|null $lastOrdrNo 历史最大 ordrNo
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    public function nextOrdrNo(?string $lastOrdrNo): string
    {
        $prefix = $this->config['ordr_no_prefix'] ?? 'CHEM';
        $digits = (int) ($this->config['ordr_no_digits'] ?? 10);
        $base = $lastOrdrNo ?: ($this->config['ordr_no_seed'] ?? ($prefix . str_repeat('0', $digits)));

        $number = (int) substr($base, strlen($prefix)) + 1;

        return $prefix . str_pad((string) $number, $digits, '0', STR_PAD_LEFT);
    }
}
