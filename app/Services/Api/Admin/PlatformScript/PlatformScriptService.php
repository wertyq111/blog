<?php

namespace App\Services\Api\Admin\PlatformScript;

use App\Models\Admin\PlatformScriptRun;
use App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript;
use App\Services\Api\Admin\PlatformScript\Scripts\ChemnetSecretCodeScript;
use App\Services\Api\Admin\PlatformScript\Scripts\SinoloansComm3LoanScript;
use App\Services\Api\Admin\PlatformScript\Support\SshRunner;
use Illuminate\Validation\ValidationException;

/**
 * 平台脚本编排：解析预览/查询、执行推送/更新、落库存档。
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/8/26
 */
class PlatformScriptService
{
    public function __construct(private readonly SshRunner $sshRunner)
    {
    }

    /**
     * 解析预览 / 查询：返回字段与将要使用的 ordrNo 或账号数据，不落库、不执行修改。
     *
     * @param string $scriptKey 脚本标识
     * @param array $params 请求参数
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    public function preview(string $scriptKey, array $params): array
    {
        $script = $this->resolveScript($scriptKey);
        $ordrNo = $script->nextOrdrNo($this->lastOrdrNo($scriptKey));

        if ($script instanceof SinoloansComm3LoanScript) {
            $text = (string) ($params['text'] ?? '');
            $fields = $this->parseOrFail($script, $text);

            return [
                'script_key' => $scriptKey,
                'fields' => $fields,
                'ordr_no' => $ordrNo,
            ];
        }

        if ($script instanceof ChemnetSecretCodeScript) {
            $login = trim((string) ($params['login'] ?? $params['text'] ?? ''));
            if ($login === '') {
                throw ValidationException::withMessages(['login' => '请输入要查询的账号 (login)']);
            }

            $queryResult = $script->queryByLogin($login);

            return [
                'script_key' => $scriptKey,
                'login' => $login,
                'found' => $queryResult['found'],
                'record' => $queryResult['record'],
                'list' => $queryResult['list'],
                'ordr_no' => $ordrNo,
            ];
        }

        if ($script instanceof BankofsunComm2CreditScript) {
            $text = (string) ($params['text'] ?? '');
            $fields = $this->parseBankofsunOrFail($script, $text);
            $previewMatch = $script->previewMatch($fields);

            return [
                'script_key' => $scriptKey,
                'fields' => $fields,
                'matched' => $previewMatch['matched'],
                'match_message' => $previewMatch['match_message'],
                'company_data' => $previewMatch['company_data'],
                'ordr_no' => $ordrNo,
            ];
        }

        throw ValidationException::withMessages(['script_key' => '未知脚本：' . $scriptKey]);
    }

    /**
     * 执行操作：解析/执行 -> 自增 ordrNo -> 远端执行/更新 -> 落库。
     *
     * @param string $scriptKey 脚本标识
     * @param array $params 请求参数
     * @return PlatformScriptRun
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    public function run(string $scriptKey, array $params): PlatformScriptRun
    {
        $script = $this->resolveScript($scriptKey);
        $ordrNo = $script->nextOrdrNo($this->lastOrdrNo($scriptKey));

        if ($script instanceof SinoloansComm3LoanScript) {
            return $this->runSinoloansScript($script, $scriptKey, $ordrNo, (string) ($params['text'] ?? ''));
        }

        if ($script instanceof ChemnetSecretCodeScript) {
            $login = trim((string) ($params['login'] ?? ''));
            $mobile = trim((string) ($params['mobile'] ?? ''));

            return $this->runChemnetScript($script, $scriptKey, $ordrNo, $login, $mobile);
        }

        if ($script instanceof BankofsunComm2CreditScript) {
            return $this->runBankofsunScript($script, $scriptKey, $ordrNo, (string) ($params['text'] ?? ''));
        }

        throw ValidationException::withMessages(['script_key' => '未知脚本：' . $scriptKey]);
    }

    /**
     * 执行 Sinoloans 脚本。
     *
     * @param SinoloansComm3LoanScript $script
     * @param string $scriptKey
     * @param string $ordrNo
     * @param string $text
     * @return PlatformScriptRun
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    private function runSinoloansScript(
        SinoloansComm3LoanScript $script,
        string $scriptKey,
        string $ordrNo,
        string $text
    ): PlatformScriptRun {
        $fields = $this->parseOrFail($script, $text);
        $requestData = $script->buildRequestData($fields, $ordrNo);
        $requestJson = json_encode($requestData, JSON_UNESCAPED_UNICODE);
        $command = $script->command(bin2hex($requestJson));
        $connection = $script->connection();

        $run = new PlatformScriptRun();
        $run->fill(array_merge($fields, [
            'script_key' => $scriptKey,
            'ordr_no' => $ordrNo,
            'raw_text' => $text,
            'request_data' => $requestJson,
        ]));

        try {
            $result = $this->sshRunner->run($connection, $command);
            $run->output = $result['output'];

            if (!empty($result['timed_out'])) {
                $run->status = 'timeout';
                $run->error = '远端执行超时（超过 ' . ((int) ($connection['timeout'] ?? 120))
                    . ' 秒），银行网关未在时限内返回，无法确认本次是否已送达，请到 sinoloans 侧日志确认。';
            } else {
                $run->status = 'success';
            }
        } catch (\Throwable $e) {
            $run->output = '';
            $run->status = 'failed';
            $run->error = $e->getMessage();
        }

        $run->edit();

        return $run;
    }

    /**
     * 执行 ChemNet 验证码手机号修改脚本。
     *
     * @param ChemnetSecretCodeScript $script
     * @param string $scriptKey
     * @param string $ordrNo
     * @param string $login
     * @param string $mobile
     * @return PlatformScriptRun
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/26
     */
    private function runChemnetScript(
        ChemnetSecretCodeScript $script,
        string $scriptKey,
        string $ordrNo,
        string $login,
        string $mobile
    ): PlatformScriptRun {
        $run = new PlatformScriptRun();
        $run->fill([
            'script_key' => $scriptKey,
            'ordr_no' => $ordrNo,
            'appl_id' => $login,
            'cust_bank_acct_no' => $mobile,
            'cntpr_nme' => $login,
            'raw_text' => "账号 (login): {$login}，目标手机号 (mobile): {$mobile}",
            'request_data' => json_encode(['login' => $login, 'mobile' => $mobile], JSON_UNESCAPED_UNICODE),
        ]);

        try {
            $updateResult = $script->updateMobile($login, $mobile);
            $run->output = json_encode($updateResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $run->status = 'success';
        } catch (\Throwable $e) {
            $run->output = '';
            $run->status = 'failed';
            $run->error = $e->getMessage();
        }

        $run->edit();

        return $run;
    }

    /**
     * 执行 bankofsun 交行企业贷 2.0 自动化流转脚本。
     *
     * @param BankofsunComm2CreditScript $script
     * @param string $scriptKey
     * @param string $ordrNo
     * @param string $text
     * @return PlatformScriptRun
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    private function runBankofsunScript(
        BankofsunComm2CreditScript $script,
        string $scriptKey,
        string $ordrNo,
        string $text
    ): PlatformScriptRun {
        $fields = $this->parseBankofsunOrFail($script, $text);
        $payload = $script->buildRequestData($fields);
        $requestJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $run = new PlatformScriptRun();
        $run->fill([
            'script_key' => $scriptKey,
            'ordr_no' => $ordrNo,
            'appl_id' => (string) ($fields['company'] ?? ''),
            'cust_bank_acct_no' => (string) ($fields['social_credit_code'] ?? ''),
            'cntpr_nme' => (string) ($fields['legal'] ?? ''),
            'cust_pay_amt' => (string) ($fields['trade_amount'] ?? ''),
            'cntrct_no' => (string) ($fields['loan_cardno'] ?? ''),
            'raw_text' => $text,
            'request_data' => $requestJson,
        ]);

        try {
            $result = $script->executeAutoFlow($payload);
            $run->output = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $run->status = 'success';
        } catch (\Throwable $e) {
            $run->output = '';
            $run->status = 'failed';
            $run->error = $e->getMessage();
        }

        $run->edit();

        return $run;
    }

    /**
     * 按脚本标识解析脚本处理器。
     *
     * @param string $scriptKey
     * @return SinoloansComm3LoanScript|ChemnetSecretCodeScript|BankofsunComm2CreditScript
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    private function resolveScript(string $scriptKey): SinoloansComm3LoanScript|ChemnetSecretCodeScript|BankofsunComm2CreditScript
    {
        $config = config('platform-script.scripts.' . $scriptKey);

        if (empty($config)) {
            throw ValidationException::withMessages(['script_key' => '未知脚本：' . $scriptKey]);
        }

        return match ($scriptKey) {
            SinoloansComm3LoanScript::KEY => new SinoloansComm3LoanScript($config),
            ChemnetSecretCodeScript::KEY => new ChemnetSecretCodeScript($config),
            BankofsunComm2CreditScript::KEY => new BankofsunComm2CreditScript($config),
            default => throw ValidationException::withMessages(['script_key' => '未知脚本：' . $scriptKey]),
        };
    }

    /**
     * 解析 bankofsun 粘贴文本，失败时转成 422 校验异常。
     *
     * @param BankofsunComm2CreditScript $script
     * @param string $text
     * @return array<string, mixed>
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    private function parseBankofsunOrFail(BankofsunComm2CreditScript $script, string $text): array
    {
        try {
            return $script->parse($text);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['text' => $e->getMessage()]);
        }
    }

    /**
     * 解析粘贴文本，失败时转成 422 校验异常，避免 500。
     *
     * @param SinoloansComm3LoanScript $script
     * @param string $text
     * @return array<string, string>
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    private function parseOrFail(SinoloansComm3LoanScript $script, string $text): array
    {
        try {
            return $script->parse($text);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['text' => $e->getMessage()]);
        }
    }

    /**
     * 取某脚本历史最大 ordrNo。
     *
     * @param string $scriptKey
     * @return string|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    private function lastOrdrNo(string $scriptKey): ?string
    {
        return PlatformScriptRun::query()
            ->where('script_key', $scriptKey)
            ->orderByDesc('ordr_no')
            ->value('ordr_no');
    }
}
