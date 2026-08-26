<?php

namespace App\Services\Api\Admin\PlatformScript\Support;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Validation\ValidationException;

/**
 * phpMyAdmin HTTP 客户端：通过 HTTP Basic Auth 与 PMA Web 认证执行 SQL。
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/8/26
 */
class PmaClient
{
    private ?Client $client = null;
    private ?string $sessionToken = null;

    /**
     * @param array $config PMA 连接配置
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * 执行 SELECT 查询并返回解析后的数据行。
     *
     * @param string $sql SQL 语句
     * @return array<int, array<string, string>> 数据行数组
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    public function query(string $sql): array
    {
        $response = $this->executeSql($sql);
        $html = $response['message'] ?? '';

        return $this->parseTableResults($html);
    }

    /**
     * 执行 UPDATE/INSERT/DELETE 等写操作并返回影响信息。
     *
     * @param string $sql SQL 语句
     * @return array{success: bool, message: string}
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    public function execute(string $sql): array
    {
        $response = $this->executeSql($sql);
        $html = $response['message'] ?? '';
        $cleanMsg = trim(strip_tags($html));

        return [
            'success' => true,
            'message' => $cleanMsg ?: '执行成功',
        ];
    }

    /**
     * 执行底层 SQL 请求并返回 PMA JSON 响应。
     *
     * @param string $sql
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    private function executeSql(string $sql): array
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->client->post('import.php', [
                'form_params' => [
                    'db' => $this->config['database'] ?? 'hub_chinachemnet',
                    'table' => $this->config['table'] ?? 'secret_code',
                    'token' => $this->sessionToken,
                    'sql_query' => $sql,
                    'ajax_request' => 'true',
                    '_nocache' => (string) microtime(true),
                ],
            ]);

            $json = json_decode((string) $response->getBody(), true);
            if (empty($json) || empty($json['success'])) {
                $errorMsg = strip_tags($json['error'] ?? $json['message'] ?? 'SQL 执行失败');
                throw new \RuntimeException('PMA 执行 SQL 失败: ' . $errorMsg);
            }

            return $json;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new \RuntimeException('请求 phpMyAdmin 异常: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 确保已完成 Basic Auth 并登录 phpMyAdmin 建立会话。
     *
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    private function ensureAuthenticated(): void
    {
        if ($this->client !== null && $this->sessionToken !== null) {
            return;
        }

        $url = $this->config['url'] ?? null;
        $basicUser = $this->config['http_basic_username'] ?? null;
        $basicPass = $this->config['http_basic_password'] ?? null;
        $pmaUser = $this->config['pma_username'] ?? null;
        $pmaPass = $this->config['pma_password'] ?? null;
        $server = $this->config['server'] ?? '1';

        if (empty($url) || empty($basicUser) || empty($basicPass) || empty($pmaUser) || empty($pmaPass)) {
            throw ValidationException::withMessages([
                'script_key' => 'ChemNet 脚本配置缺失，请检查 .env 中的 CHEMNET_* 凭据配置。',
            ]);
        }

        $jar = new CookieJar();
        $this->client = new Client([
            'base_uri' => rtrim($url, '/') . '/',
            'auth' => [$basicUser, $basicPass],
            'cookies' => $jar,
            'timeout' => 20,
        ]);

        try {
            // 1. 获取登录页以提取初始 token
            $initRes = $this->client->get('index.php');
            $initHtml = (string) $initRes->getBody();
            if (!preg_match('/name="token" value="([a-f0-9]+)"/', $initHtml, $m)) {
                throw new \RuntimeException('未能从 phpMyAdmin 登录页面提取 token');
            }
            $initialToken = $m[1];

            // 2. 登录 phpMyAdmin
            $loginRes = $this->client->post('index.php', [
                'form_params' => [
                    'pma_username' => $pmaUser,
                    'pma_password' => $pmaPass,
                    'server' => $server,
                    'target' => 'index.php',
                    'token' => $initialToken,
                ],
            ]);

            $loginHtml = (string) $loginRes->getBody();
            if (preg_match('/token=([a-f0-9]{32})/', $loginHtml, $m2)) {
                $this->sessionToken = $m2[1];
            } else {
                $this->sessionToken = $initialToken;
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new \RuntimeException('连接 phpMyAdmin 失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 解析 PMA 返回的 HTML 结果表格。
     *
     * @param string $html
     * @return array<int, array<string, string>>
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    private function parseTableResults(string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);

        // 提取带有 data-column 的表头
        $headers = [];
        $thNodes = $xpath->query('//table[contains(@class, "data")]//thead//th[@data-column]');
        if ($thNodes->length === 0) {
            $thNodes = $xpath->query('//table//thead//th[@data-column]');
        }

        foreach ($thNodes as $th) {
            $headers[] = $th->getAttribute('data-column');
        }

        // 提取数据行
        $rows = [];
        $trNodes = $xpath->query('//table[contains(@class, "data")]//tbody//tr[contains(@class, "odd") or contains(@class, "even")]');
        if ($trNodes->length === 0) {
            $trNodes = $xpath->query('//table//tbody//tr');
        }

        foreach ($trNodes as $tr) {
            $tdNodes = $xpath->query('.//td[contains(@class, "data")]', $tr);
            if ($tdNodes->length === 0) {
                continue;
            }

            $row = [];
            for ($i = 0; $i < $tdNodes->length; $i++) {
                $colName = $headers[$i] ?? (string) $i;
                $row[$colName] = trim($tdNodes->item($i)->textContent);
            }
            if (!empty($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
