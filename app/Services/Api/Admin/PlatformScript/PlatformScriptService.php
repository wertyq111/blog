<?php

namespace App\Services\Api\Admin\PlatformScript;

use App\Models\Admin\PlatformScriptRun;
use App\Services\Api\Admin\PlatformScript\Scripts\SinoloansComm3LoanScript;
use App\Services\Api\Admin\PlatformScript\Support\SshRunner;
use Illuminate\Validation\ValidationException;

/**
 * 平台脚本编排：解析预览、执行推送、落库存档。
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/7/23
 */
class PlatformScriptService
{
    public function __construct(private readonly SshRunner $sshRunner)
    {
    }

    /**
     * 解析预览：返回字段与将要使用的 ordrNo，不发送、不落库。
     *
     * @param string $scriptKey
     * @param string $text
     * @return array{script_key: string, fields: array<string, string>, ordr_no: string}
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function preview(string $scriptKey, string $text): array
    {
        $script = $this->resolveScript($scriptKey);
        $fields = $this->parseOrFail($script, $text);
        $ordrNo = $script->nextOrdrNo($this->lastOrdrNo($scriptKey));

        return [
            'script_key' => $scriptKey,
            'fields' => $fields,
            'ordr_no' => $ordrNo,
        ];
    }

    /**
     * 执行推送：解析 -> 自增 ordrNo -> SSH 远端执行 -> 落库。
     *
     * @param string $scriptKey
     * @param string $text
     * @return PlatformScriptRun
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function run(string $scriptKey, string $text): PlatformScriptRun
    {
        $script = $this->resolveScript($scriptKey);
        $fields = $this->parseOrFail($script, $text);
        $ordrNo = $script->nextOrdrNo($this->lastOrdrNo($scriptKey));
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
                // 超时不当作成功：远端可能仍在跑、输出多半没读回，是否已送达无法从这里判断
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
     * 按脚本标识解析脚本处理器。新增脚本在此登记分支。
     *
     * @param string $scriptKey
     * @return SinoloansComm3LoanScript
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    private function resolveScript(string $scriptKey): SinoloansComm3LoanScript
    {
        $config = config('platform-script.scripts.' . $scriptKey);

        if (empty($config)) {
            throw ValidationException::withMessages(['script_key' => '未知脚本：' . $scriptKey]);
        }

        return match ($scriptKey) {
            SinoloansComm3LoanScript::KEY => new SinoloansComm3LoanScript($config),
            default => throw ValidationException::withMessages(['script_key' => '未知脚本：' . $scriptKey]),
        };
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
     * 取某脚本历史最大 ordrNo（定宽补零，字典序即数值序）。
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
