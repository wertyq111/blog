<?php

namespace App\Services\Api\Admin\PlatformScript\Scripts;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * ChemNet 验证码手机号修改脚本。
 *
 * 通过 chemnet 官方接口（`_a=secret_code`）查询与修改账号绑定的手机号，
 * 不再直连 hub_chinachemnet.secret_code 数据表。
 *
 * 接口文档：docs/CHEMNET_SECRET_CODE_API.md（chemnet_news 项目）
 * 注意：接口按白名单输出字段，不返回 code / num / remark / post_ip。
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/9/3
 */
class ChemnetSecretCodeScript
{
    public const KEY = 'chemnet-secret-code';

    /**
     * 接口失败码到中文说明的映射。
     */
    private const EXP_MESSAGES = [
        'invalid_login' => '账号 (login) 为空',
        'invalid_id' => '记录 id 非法',
        'invalid_mobile' => '手机号格式不合法（需 11 位、1 开头）',
        'not_found' => '接口未找到对应记录',
        'failed' => '接口执行更新失败',
    ];

    /**
     * @param array $config 该脚本在 config/platform-script.php 的配置片段
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * 根据账号（login）查询绑定信息。
     *
     * login 在接口侧是唯一索引，最多返回一条记录；查无记录时 found 为 false，不抛异常。
     *
     * @param string $login
     * @return array{found: bool, record: ?array<string, string>}
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/3
     */
    public function queryByLogin(string $login): array
    {
        $cleanLogin = trim($login);
        if ($cleanLogin === '') {
            throw ValidationException::withMessages(['login' => '请输入要查询的账号 (login)']);
        }

        $json = $this->callApi('get_bind_info', ['login' => $cleanLogin]);

        if (($json['exp'] ?? '') === 'not_found') {
            return [
                'found' => false,
                'record' => null,
            ];
        }

        $this->assertSuccess($json, '查询绑定信息');

        return [
            'found' => true,
            'record' => $json['data'] ?? null,
        ];
    }

    /**
     * 修改指定账号（login）绑定的手机号。
     *
     * 接口按记录 id 修改，故先查一次拿到 id 再调用修改接口。
     *
     * @param string $login
     * @param string $newMobile
     * @return array{login: string, record_id: string, old_mobile: string, new_mobile: string, affected_message: string, current_record: ?array<string, string>}
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/3
     */
    public function updateMobile(string $login, string $newMobile): array
    {
        $cleanLogin = trim($login);
        $cleanMobile = trim($newMobile);

        if ($cleanLogin === '') {
            throw ValidationException::withMessages(['login' => '账号 (login) 不能为空']);
        }
        if (!preg_match('/^1\d{10}$/', $cleanMobile)) {
            throw ValidationException::withMessages(['mobile' => '请输入有效的 11 位手机号码']);
        }

        // 1. 先查当前绑定记录，拿到接口所需的记录 id
        $before = $this->queryByLogin($cleanLogin);
        if (empty($before['found']) || empty($before['record'])) {
            throw ValidationException::withMessages(['login' => "接口未查到账号 [{$cleanLogin}] 的绑定记录"]);
        }

        $recordId = (string) ($before['record']['id'] ?? '');

        // 2. 调用修改接口
        $json = $this->callApi('change_mobile', ['id' => $recordId, 'mobile' => $cleanMobile], 'post');
        $this->assertSuccess($json, '修改绑定手机号');

        $data = $json['data'] ?? [];

        // 3. 再查一次拿最新记录
        $after = $this->queryByLogin($cleanLogin);

        return [
            'login' => $cleanLogin,
            'record_id' => $recordId,
            'old_mobile' => (string) ($data['mobile_old'] ?? ($before['record']['mobile'] ?? '')),
            'new_mobile' => $cleanMobile,
            'affected_message' => (string) ($json['exp'] ?? ''),
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

    /**
     * 获取接口地址。
     *
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/3
     */
    public function apiUrl(): string
    {
        $url = (string) ($this->config['api_url'] ?? '');
        if ($url === '') {
            throw new \RuntimeException('未配置 chemnet 接口地址（CHEMNET_SECRET_CODE_API_URL）');
        }

        return $url;
    }

    /**
     * 获取请求超时时间（秒）。
     *
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/3
     */
    public function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? 30);
    }

    /**
     * 调用 secret_code 接口并返回解析后的 JSON。
     *
     * 接口以 `?_a=secret_code&f=<function>` 路由，响应 Content-Type 为 text/html，需自行按 JSON 解析。
     *
     * @param string $function 接口方法名（get_bind_info / change_mobile）
     * @param array $params 业务参数
     * @param string $method 请求方式：get / post
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/3
     */
    private function callApi(string $function, array $params, string $method = 'get'): array
    {
        $route = ['_a' => 'secret_code', 'f' => $function];

        // GET 的业务参数必须和路由参数拼在同一个 query string 里：
        // Http::get($url, $params) 会用 $params 整体替换 URL 上已有的 query，把 _a / f 冲掉。
        $url = $this->apiUrl() . '?' . http_build_query(
            $method === 'post' ? $route : array_merge($route, $params)
        );

        $request = Http::timeout($this->timeout());
        $response = $method === 'post'
            ? $request->asForm()->post($url, $params)
            : $request->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('chemnet 接口返回 HTTP 异常：' . $response->status());
        }

        $json = json_decode($response->body(), true);
        if (!is_array($json)) {
            throw new \RuntimeException('chemnet 接口返回非 JSON 内容：' . mb_substr(trim($response->body()), 0, 200));
        }

        return $json;
    }

    /**
     * 校验接口业务状态，失败时抛出带中文说明的异常。
     *
     * @param array $json 接口响应
     * @param string $action 当前动作说明
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/3
     */
    private function assertSuccess(array $json, string $action): void
    {
        if ((int) ($json['state'] ?? 0) === 1) {
            return;
        }

        $exp = (string) ($json['exp'] ?? '');
        $message = self::EXP_MESSAGES[$exp] ?? '未知错误';

        throw new \RuntimeException("{$action}失败 [{$exp}]: {$message}");
    }
}
