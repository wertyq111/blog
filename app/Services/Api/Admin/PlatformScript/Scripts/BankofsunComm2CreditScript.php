<?php

namespace App\Services\Api\Admin\PlatformScript\Scripts;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * bankofsun 交行企业贷 2.0 授信测试数据生成脚本。
 *
 * 把企业信息文本解析成 comm2_auto_flow 请求字段，生成自增 ordrNo，
 * 提供企业档案匹配预览，并自动执行阶段一至阶段三授信流转。
 *
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/8/27
 */
class BankofsunComm2CreditScript
{
    public const KEY = 'bankofsun-comm2-credit';

    /**
     * 文本标签 => 目标字段映射（按解析优先级排序，更具体长词优先）。
     *
     * @var array<string, string[]>
     */
    private const LABEL_MAP = [
        'company'            => ['企业名称', '企业全称', '公司名称', '客户名称', '企业', 'company'],
        'social_credit_code' => ['统一社会信用代码', '社会信用代码', '信用代码', '纳税人识别号', '税号', '注册号', 'social_credit_code', 'credit_code'],
        'ecif_cst_no'        => ['法人客户号', '法人ECIF', 'ECIF客户号', '法人ecif', 'ecif_cst_no', 'ecif'],
        'legal'              => ['法人姓名', '法定代表人', '法人代表', '法人名称', '法人', 'legal'],
        'id_card'            => ['法人身份证号', '法人身份证', '身份证号', '身份证', '证件号码', '证件号', 'id_card'],
        'mobile'             => ['手机号码', '手机号', '联系电话', '电话', 'mobile'],
        'buyer_company_type' => ['企业类型', '企业规模', '客户类型', '买方企业类型', 'buyer_company_type'],
        'trade_amount'       => ['近两年与核心企业平均交易量', '近两年平均交易量', '年平均交易量', '年交易量', '平均交易量', '交易量', '交易额', 'trade_amount'],
        'loan_cardno'        => ['对公客户号', '贷款卡号', '贷款卡编码', '客户号', '公户号', 'loan_cardno'],
        'amount'             => ['申请额度', '申请金额', '额度', '授信额度', 'amount'],
    ];

    /**
     * @param array $config 该脚本在 config/platform-script.php 的配置片段
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * 解析粘贴文本为请求字段。
     *
     * @param string $text
     * @return array<string, mixed>
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
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
            // 去掉值两端空白及中英文逗号
            $value = preg_replace('/^[\s,，]+|[\s,，]+$/u', '', $parts[1]);

            if ($label === '' || $value === '') {
                continue;
            }

            $matchedField = null;
            // 优先精确匹配
            foreach (self::LABEL_MAP as $field => $aliases) {
                if (isset($fields[$field])) {
                    continue;
                }
                foreach ($aliases as $alias) {
                    if (strcasecmp($label, $alias) === 0) {
                        $matchedField = $field;
                        break 2;
                    }
                }
            }

            // 次级模糊匹配（跳过特定冲突）
            if ($matchedField === null) {
                foreach (self::LABEL_MAP as $field => $aliases) {
                    if (isset($fields[$field])) {
                        continue;
                    }
                    // 特殊防冲突：包含客户号/ECIF 时不能落入 legal
                    if ($field === 'legal' && (str_contains($label, '客户号') || stripos($label, 'ecif') !== false)) {
                        continue;
                    }
                    foreach ($aliases as $alias) {
                        if (str_contains($label, $alias)) {
                            $matchedField = $field;
                            break 2;
                        }
                    }
                }
            }

            if ($matchedField !== null) {
                $fields[$matchedField] = $value;
            }
        }

        // 核心必填校验
        if (empty($fields['company'])) {
            throw new \InvalidArgumentException('缺少必须字段：企业名称');
        }
        if (empty($fields['social_credit_code'])) {
            throw new \InvalidArgumentException('缺少必须字段：统一社会信用代码');
        }

        // 默认值填充与单位清洗
        $fields['legal'] = $fields['legal'] ?? '';
        $fields['id_card'] = $fields['id_card'] ?? '';
        $fields['mobile'] = $fields['mobile'] ?? '';
        $fields['buyer_company_type'] = $this->normalizeBuyerCompanyType($fields['buyer_company_type'] ?? '贸易型企业');
        $fields['trade_amount'] = $this->parseTradeAmount($fields['trade_amount'] ?? null);
        $fields['loan_cardno'] = $fields['loan_cardno'] ?? '';
        $fields['amount'] = $this->parseAmount($fields['amount'] ?? null);
        $fields['ecif_cst_no'] = $fields['ecif_cst_no'] ?? '';

        return $fields;
    }

    /**
     * 归一化企业类型。
     *
     * 生产型/微型企业映射为 'S'（对应交总行二期 company_type = 0）；
     * 贸易型企业映射为 'M'（对应交总行二期 company_type = 1）。
     *
     * @param string $type
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    public function normalizeBuyerCompanyType(string $type): string
    {
        $clean = trim($type);
        if (preg_match('/生产|微|S|0/i', $clean)) {
            return 'S';
        }
        return 'M';
    }

    /**
     * 解析平均交易量（转换为万元数值）。
     *
     * @param mixed $value
     * @return float
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    public function parseTradeAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 5000.0;
        }

        $str = (string) $value;
        // 提取前导数字（支持小数）
        if (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $str, $matches)) {
            $num = (float) $matches[1];
            // 若输入为元（例如 40000000 或 50000000，>= 1000000），折算为万元
            if ($num >= 1000000 && !str_contains($str, '万')) {
                return round($num / 10000, 2);
            }
            return $num;
        }

        return 5000.0;
    }

    /**
     * 解析申请额度（转换为分）。
     *
     * @param mixed $value
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    public function parseAmount(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 60000000;
        }

        $str = (string) $value;
        if (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $str, $matches)) {
            $num = (float) $matches[1];
            // 若带"万元"或数值较小（如 60 或 6000），按万元换算为分
            if (str_contains($str, '万') || $num <= 10000) {
                return (int) ($num * 10000 * 100);
            }
            return (int) $num;
        }

        return 60000000;
    }

    /**
     * 调用远程接口执行企业档案匹配预览。
     *
     * @param array<string, mixed> $fields
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    public function previewMatch(array $fields): array
    {
        try {
            $response = Http::timeout($this->timeout())
                ->asJson()
                ->post($this->apiUrl(), [
                    'action' => 'match_company',
                    'social_credit_code' => $fields['social_credit_code'],
                    'company' => $fields['company'],
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException('远程接口调用失败，HTTP 状态码：' . $response->status());
            }

            $json = $response->json();

            return [
                'fields' => $fields,
                'matched' => (bool) ($json['matched'] ?? false),
                'match_message' => $json['message'] ?? '',
                'company_data' => $json['data'] ?? null,
            ];
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['api' => '匹配企业档案失败：' . $e->getMessage()]);
        }
    }

    /**
     * 组装远端自动化流转请求 payload。
     *
     * @param array<string, mixed> $fields
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    public function buildRequestData(array $fields): array
    {
        $payload = [
            'action' => 'auto_all',
            'company' => $fields['company'],
            'social_credit_code' => $fields['social_credit_code'],
            'legal' => $fields['legal'] ?? '',
            'id_card' => $fields['id_card'] ?? '',
            'mobile' => $fields['mobile'] ?? '',
            'buyer_company_type' => $fields['buyer_company_type'] ?? '贸易型企业',
            'trade_amount' => $fields['trade_amount'] ?? 5000,
        ];

        if (!empty($fields['loan_cardno'])) {
            $payload['loan_cardno'] = $fields['loan_cardno'];
        }
        if (!empty($fields['amount'])) {
            $payload['amount'] = (int) $fields['amount'];
        }
        if (!empty($fields['ecif_cst_no'])) {
            $payload['ecif_cst_no'] = $fields['ecif_cst_no'];
        }

        return $payload;
    }

    /**
     * 调用远程接口执行一键自动化流转。
     *
     * @param array $payload
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    public function executeAutoFlow(array $payload): array
    {
        $response = Http::timeout($this->timeout())
            ->asJson()
            ->post($this->apiUrl(), $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('远程流转接口返回 HTTP 异常：' . $response->status());
        }

        $json = $response->json();
        if (($json['status'] ?? '') !== 'success') {
            $errorMsg = $json['message'] ?? '未知错误';
            $errorCode = $json['code'] ?? '';
            throw new \RuntimeException("接口执行失败 [{$errorCode}]: {$errorMsg}");
        }

        return $json;
    }

    /**
     * 根据历史最大 ordrNo 计算下一个（无历史时以种子 +1）。
     *
     * @param string|null $lastOrdrNo
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    public function nextOrdrNo(?string $lastOrdrNo): string
    {
        $prefix = $this->config['ordr_no_prefix'] ?? 'BOSC';
        $digits = (int) ($this->config['ordr_no_digits'] ?? 10);
        $base = $lastOrdrNo ?: ($this->config['ordr_no_seed'] ?? ($prefix . str_repeat('0', $digits)));

        $number = (int) substr($base, strlen($prefix)) + 1;

        return $prefix . str_pad((string) $number, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * 获取 API 请求地址。
     *
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    public function apiUrl(): string
    {
        return $this->config['api_url'] ?? 'http://api.dev.bankofsun.cn/bankofsun/comm2_auto_flow.php';
    }

    /**
     * 获取请求超时时间（秒）。
     *
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/8/27
     */
    public function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? 30);
    }
}
