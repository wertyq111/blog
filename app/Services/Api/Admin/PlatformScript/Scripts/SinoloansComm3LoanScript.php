<?php

namespace App\Services\Api\Admin\PlatformScript\Scripts;

/**
 * sinoloans 放款测试推送脚本。
 *
 * 把银行发来的申请文本解析成 comm3-test loan 的请求字段，生成自增 ordrNo，
 * 组装成远端 loanAction 需要的请求结构，并给出远端执行命令。
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/7/23
 */
class SinoloansComm3LoanScript
{
    public const KEY = 'sinoloans-comm3-loan';

    /**
     * 粘贴标签 => 目标字段。值为可接受的标签子串（容忍别名）。
     *
     * @var array<string, string[]>
     */
    private const LABEL_MAP = [
        'appl_id'           => ['申请编号'],
        'cust_bank_acct_no' => ['收款银行账户', '收款账户'],
        'cntrct_no'         => ['合同号'],
        'cust_pay_amt'      => ['提款金额'],
        'cntpr_nme'         => ['交易对手户名', '交易对手名', '交易对手'],
        'actcpe_bchnw_id'   => ['开户网点号'],
        'actope_bchnw_nme'  => ['开户网点名'],
    ];

    /**
     * @param array $config 该脚本在 config/platform-script.php 的配置片段
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * 解析粘贴文本为 7 个请求字段。
     *
     * @param string $text
     * @return array<string, string>
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function parse(string $text): array
    {
        $fields = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            // 按首个中英文冒号拆分标签与值
            $parts = preg_split('/[:：]/u', $line, 2);
            if (count($parts) < 2) {
                continue;
            }

            $label = preg_replace('/\s+/u', '', $parts[0]);
            // 去掉值两端空白及中英文逗号（用 /u 按字符处理，避免字节裁剪破坏尾部中文，如「行」被误删尾字节）
            $value = preg_replace('/^[\s,，]+|[\s,，]+$/u', '', $parts[1]);

            if ($label === '' || $value === '') {
                continue;
            }

            foreach (self::LABEL_MAP as $field => $aliases) {
                if (isset($fields[$field])) {
                    continue;
                }
                foreach ($aliases as $alias) {
                    if (str_contains($label, $alias)) {
                        $fields[$field] = $value;
                        break 2;
                    }
                }
            }
        }

        $missing = array_diff(array_keys(self::LABEL_MAP), array_keys($fields));
        if (!empty($missing)) {
            $labels = array_map(static fn($f) => self::LABEL_MAP[$f][0], $missing);
            throw new \InvalidArgumentException('缺少字段：' . implode('、', $labels));
        }

        return $fields;
    }

    /**
     * 根据历史最大 ordrNo 计算下一个（无历史时以种子 +1）。
     *
     * @param string|null $lastOrdrNo 历史最大 ordrNo
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function nextOrdrNo(?string $lastOrdrNo): string
    {
        $prefix = $this->config['ordr_no_prefix'];
        $digits = (int) $this->config['ordr_no_digits'];

        $base = $lastOrdrNo ?: $this->config['ordr_no_seed'];
        $number = (int) substr($base, strlen($prefix)) + 1;

        return $prefix . str_pad((string) $number, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * 组装远端 loanAction 需要的请求结构。
     *
     * @param array<string, string> $fields
     * @param string $ordrNo
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function buildRequestData(array $fields, string $ordrNo): array
    {
        return [
            'applId' => $fields['appl_id'],
            'ordrNo' => $ordrNo,
            'transferItemList' => [
                [
                    'cntrctNo' => $fields['cntrct_no'],
                    'cntprNme' => $fields['cntpr_nme'],
                    'custBankAcctNo' => $fields['cust_bank_acct_no'],
                    'custPayAmt' => $fields['cust_pay_amt'],
                    'actcpeBchnwId' => $fields['actcpe_bchnw_id'],
                    'actopeBchnwNme' => $fields['actope_bchnw_nme'],
                ],
            ],
            'remark1' => '',
            'remark2' => '',
            'remark3' => '',
        ];
    }

    /**
     * 远端执行命令。payload 用 hex 编码：远端 CLI 路由以 explode("=") 拆参，base64 的 "=" 补位会被截断。
     *
     * @param string $payloadHex
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function command(string $payloadHex): string
    {
        return sprintf(
            'cd %s && %s payload=%s',
            $this->config['remote_path'],
            $this->config['script_command'],
            $payloadHex
        );
    }

    /**
     * 该脚本使用的 SSH 连接配置。
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/23
     */
    public function connection(): array
    {
        return config('platform-script.connections.' . $this->config['connection']);
    }
}
