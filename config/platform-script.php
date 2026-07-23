<?php

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
    ],

    // SSH 连接配置。密码等敏感信息只走 .env，不写默认值，缺失时由 SshRunner 直接报错。
    'connections' => [
        'sinoloans' => [
            'host' => env('SINOLOANS_SSH_HOST', 'www2.dev.sinoloans.cn'),
            'port' => (int) env('SINOLOANS_SSH_PORT', 22),
            'username' => env('SINOLOANS_SSH_USERNAME', 'sinoloans'),
            'password' => env('SINOLOANS_SSH_PASSWORD'),
            'timeout' => (int) env('SINOLOANS_SSH_TIMEOUT', 120),
        ],
    ],
];
