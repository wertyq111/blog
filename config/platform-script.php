<?php

use App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript;
use App\Services\Api\Admin\PlatformScript\Scripts\ChemnetSecretCodeScript;
use App\Services\Api\Admin\PlatformScript\Scripts\SinoloansComm3LoanScript;

return [
    // 默认脚本（前端下拉默认项，为将来多脚本留位）
    'default_script' => SinoloansComm3LoanScript::KEY,

    // 已注册脚本。新增脚本时在此登记，并在 PlatformScriptService::resolveScript 里补一个分支。
    'scripts' => [
        SinoloansComm3LoanScript::KEY => [
            'name' => 'sinoloans 交行个经贷放款测试推送',
            'connection' => 'sinoloans',
            'remote_path' => env('SINOLOANS_REMOTE_PATH', '/var/www/html/toocle/sinoloans'),
            'script_command' => 'php public/script.php controller=comm3-test action=loan',
            'ordr_no_prefix' => 'SYBG',
            // ordrNo 数字部分补零位数（SYBG + 16 位 = 20 位）
            'ordr_no_digits' => 16,
            // 无历史时的种子（脚本当前写死值），下一个自增为 56
            'ordr_no_seed' => 'SYBG0000000000000055',
        ],
        ChemnetSecretCodeScript::KEY => [
            'name' => 'ChemNet 验证码手机号修改 (.secret_code)',
            'api_url' => env('CHEMNET_SECRET_CODE_API_URL', 'http://api.b2b.chemnet.com/chemnet/index.php'),
            'ordr_no_prefix' => 'CHEM',
            'ordr_no_digits' => 10,
            'ordr_no_seed' => 'CHEM0000000000',
            'timeout' => (int) env('CHEMNET_SECRET_CODE_TIMEOUT', 30),
        ],
        BankofsunComm2CreditScript::KEY => [
            'name' => 'bankofsun 交行企业贷2.0授信测试数据生成',
            'api_url' => env('BANKOFSUN_COMM2_API_URL', 'http://api.dev.bankofsun.cn/bankofsun/comm2_auto_flow.php'),
            'ordr_no_prefix' => 'BOSC',
            'ordr_no_digits' => 10,
            'ordr_no_seed' => 'BOSC0000000000',
            'timeout' => (int) env('BANKOFSUN_COMM2_TIMEOUT', 30),
        ],
    ],

    // SSH 连接配置。密码等敏感信息只走 .env，不写默认值，缺失时由 SshRunner 直接报错。
    'connections' => [
        'sinoloans' => [
            'host' => env('SINOLOANS_SSH_HOST'),
            'port' => (int) env('SINOLOANS_SSH_PORT', 22),
            'username' => env('SINOLOANS_SSH_USERNAME'),
            'password' => env('SINOLOANS_SSH_PASSWORD'),
            'timeout' => (int) env('SINOLOANS_SSH_TIMEOUT', 120),
        ],
    ],
];

