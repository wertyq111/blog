<?php

use App\Models\Admin\PlatformScriptRun;
use App\Models\User\User;
use App\Services\Api\Admin\PlatformScript\Scripts\SinoloansComm3LoanScript;
use App\Services\Api\Admin\PlatformScript\Support\SshRunner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    Schema::dropAllTables();

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('username')->nullable();
        $table->string('email')->nullable()->unique();
        $table->string('phone')->nullable()->unique();
        $table->string('openid')->nullable()->unique();
        $table->string('unionid')->nullable()->unique();
        $table->string('password')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->integer('status')->default(0);
        $table->rememberToken();
        $table->unsignedInteger('created_at')->default(0);
        $table->integer('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('code')->nullable();
        $table->unsignedInteger('deleted_at')->default(0);
    });

    Schema::create('user_role', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id')->default(0);
        $table->unsignedBigInteger('role_id')->default(0);
    });

    Schema::create('platform_script_runs', function (Blueprint $table) {
        $table->id();
        $table->string('script_key', 64)->index();
        $table->string('ordr_no', 40)->index();
        $table->string('appl_id', 64)->default('');
        $table->string('cntrct_no', 64)->default('');
        $table->string('cntpr_nme', 128)->default('');
        $table->string('cust_bank_acct_no', 64)->default('');
        $table->string('cust_pay_amt', 32)->default('');
        $table->string('actcpe_bchnw_id', 64)->default('');
        $table->string('actope_bchnw_nme', 255)->default('');
        $table->text('raw_text');
        $table->text('request_data');
        $table->longText('output');
        $table->string('status', 16);
        $table->text('error')->nullable();
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });
});

it('解析预览返回字段与自增 ordrNo（无历史时以种子 +1）', function () {
    $token = platformScriptLoginAsAdmin();

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/preview', [
            'script_key' => SinoloansComm3LoanScript::KEY,
            'text' => platformScriptSampleText(),
        ]);

    // 响应经 After 中间件包裹为 {code, data}，preview 裸数组落在 data 下（下划线键）
    $response
        ->assertOk()
        ->assertJsonPath('data.fields.appl_id', 'SYBG5231803687502921728')
        ->assertJsonPath('data.fields.cust_bank_acct_no', '6222620110099748528')
        ->assertJsonPath('data.fields.cntrct_no', '202901331160999077200000001')
        ->assertJsonPath('data.fields.cust_pay_amt', '10000')
        ->assertJsonPath('data.fields.cntpr_nme', '王瑾诗')
        ->assertJsonPath('data.fields.actcpe_bchnw_id', '01310207999')
        ->assertJsonPath('data.fields.actope_bchnw_nme', '交通银行上海陕西南路支行')
        ->assertJsonPath('data.ordr_no', 'SYBG0000000000000056');
});

it('ordrNo 基于历史最大值自增', function () {
    $token = platformScriptLoginAsAdmin();
    platformScriptCreateRun(['ordr_no' => 'SYBG0000000000000100']);
    platformScriptCreateRun(['ordr_no' => 'SYBG0000000000000099']);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/preview', [
            'script_key' => SinoloansComm3LoanScript::KEY,
            'text' => platformScriptSampleText(),
        ]);

    $response->assertOk()->assertJsonPath('data.ordr_no', 'SYBG0000000000000101');
});

it('缺少字段时预览返回 422', function () {
    $token = platformScriptLoginAsAdmin();

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/preview', [
            'script_key' => SinoloansComm3LoanScript::KEY,
            'text' => "申请编号:SYBG5231803687502921728\n收款银行账户:6222620110099748528",
        ]);

    // After 中间件把校验异常也包成 HTTP 200 + body code=422
    $response->assertOk()->assertJsonPath('code', 422);
});

it('执行推送落库并返回远端输出', function () {
    $token = platformScriptLoginAsAdmin();

    $this->mock(SshRunner::class, function ($mock) {
        $mock->shouldReceive('run')
            ->once()
            ->andReturn(['output' => "请求报文: {...}\n响应报文: {\"code\":\"000000\"}\n", 'exit_status' => 0]);
    });

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/run', [
            'script_key' => SinoloansComm3LoanScript::KEY,
            'text' => platformScriptSampleText(),
        ]);

    // run 走 BaseResource，字段会转成小驼峰
    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.ordrNo', 'SYBG0000000000000056')
        ->assertJsonPath('data.applId', 'SYBG5231803687502921728');

    $run = PlatformScriptRun::query()->where('ordr_no', 'SYBG0000000000000056')->firstOrFail();

    expect($run->status)->toBe('success')
        ->and($run->output)->toContain('响应报文')
        ->and(json_decode($run->request_data, true)['transferItemList'][0]['cntprNme'])->toBe('王瑾诗');
});

it('远端执行超时时落成 timeout 状态而非 success', function () {
    $token = platformScriptLoginAsAdmin();

    $this->mock(SshRunner::class, function ($mock) {
        $mock->shouldReceive('run')
            ->once()
            ->andReturn(['output' => '', 'exit_status' => null, 'timed_out' => true]);
    });

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/run', [
            'script_key' => SinoloansComm3LoanScript::KEY,
            'text' => platformScriptSampleText(),
        ]);

    $response->assertOk()->assertJsonPath('data.status', 'timeout');

    $run = PlatformScriptRun::query()->where('ordr_no', 'SYBG0000000000000056')->firstOrFail();
    expect($run->status)->toBe('timeout')
        ->and($run->error)->toContain('超时');
});

it('payload 以 hex 编码且能被还原成请求数据', function () {
    $script = new SinoloansComm3LoanScript([
        'connection' => 'sinoloans',
        'remote_path' => '/var/www/html/toocle/sinoloans',
        'script_command' => 'php public/script.php controller=comm3-test action=loan',
        'ordr_no_prefix' => 'SYBG',
        'ordr_no_digits' => 16,
        'ordr_no_seed' => 'SYBG0000000000000055',
    ]);

    expect($script->nextOrdrNo(null))->toBe('SYBG0000000000000056')
        ->and($script->nextOrdrNo('SYBG0000000000000100'))->toBe('SYBG0000000000000101');

    $fields = $script->parse(platformScriptSampleText());
    $requestData = $script->buildRequestData($fields, 'SYBG0000000000000056');
    $hex = bin2hex(json_encode($requestData, JSON_UNESCAPED_UNICODE));
    $command = $script->command($hex);

    // hex 只含 0-9a-f，命令行安全，且远端能 hex2bin 还原成同一份请求数据
    expect($command)->toContain('payload=' . $hex)
        ->and($hex)->toMatch('/^[0-9a-f]+$/')
        ->and(json_decode(hex2bin($hex), true))->toBe($requestData);
});

it('ChemNet 脚本流水号自增正确', function () {
    $script = new \App\Services\Api\Admin\PlatformScript\Scripts\ChemnetSecretCodeScript([
        'ordr_no_prefix' => 'CHEM',
        'ordr_no_digits' => 10,
        'ordr_no_seed' => 'CHEM0000000000',
    ]);

    expect($script->nextOrdrNo(null))->toBe('CHEM0000000001')
        ->and($script->nextOrdrNo('CHEM0000000008'))->toBe('CHEM0000000009');
});

it('bankofsun 脚本流水号自增正确', function () {
    $script = new \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript([
        'ordr_no_prefix' => 'BOSC',
        'ordr_no_digits' => 10,
        'ordr_no_seed' => 'BOSC0000000000',
    ]);

    expect($script->nextOrdrNo(null))->toBe('BOSC0000000001')
        ->and($script->nextOrdrNo('BOSC0000000005'))->toBe('BOSC0000000006');
});

it('bankofsun 脚本解析文本并预览匹配结果', function () {
    $token = platformScriptLoginAsAdmin();

    \Illuminate\Support\Facades\Http::fake([
        'http://api.dev.bankofsun.cn/bankofsun/comm2_auto_flow.php' => \Illuminate\Support\Facades\Http::response([
            'status' => 'success',
            'matched' => true,
            'message' => '成功匹配到企业档案',
            'data' => [
                'cid' => 10001680,
                'company' => '起起落落测试公司八',
                'social_credit_code' => '9144080021832648A3',
            ],
        ], 200),
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/preview', [
            'script_key' => \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY,
            'text' => platformScriptBankofsunSampleText(),
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.fields.company', '起起落落测试公司八')
        ->assertJsonPath('data.fields.social_credit_code', '9144080021832648A3')
        ->assertJsonPath('data.fields.legal', '王五')
        ->assertJsonPath('data.fields.buyer_company_type', 'M')
        ->assertJsonPath('data.matched', true)
        ->assertJsonPath('data.company_data.cid', 10001680)
        ->assertJsonPath('data.ordr_no', 'BOSC0000000001');
});

it('bankofsun 脚本正确解析生产型企业、法人客户号与带万元的交易量', function () {
    $script = new \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript([]);
    $text = <<<TEXT
对公客户号:0115684030511238
企业名称:会当临绝顶测试公司二六
统一社会信用代码:91440800249042353U
法人客户号:0115652386057386
法人名称:陈宁
法人身份证号:540102198105309697
企业类型：生产型企业
近两年与核心企业平均交易量：4000万元
TEXT;

    $fields = $script->parse($text);

    expect($fields['company'])->toBe('会当临绝顶测试公司二六')
        ->and($fields['social_credit_code'])->toBe('91440800249042353U')
        ->and($fields['legal'])->toBe('陈宁')
        ->and($fields['ecif_cst_no'])->toBe('0115652386057386')
        ->and($fields['id_card'])->toBe('540102198105309697')
        ->and($fields['buyer_company_type'])->toBe('S')
        ->and($fields['trade_amount'])->toBe(4000.0)
        ->and($fields['loan_cardno'])->toBe('0115684030511238');
});

it('bankofsun 已建档企业未传字段时自动继承已有档案原值，不覆盖不清空', function () {
    $script = new \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript([]);
    $text = <<<TEXT
企业名称:会当临绝顶测试公司二六
统一社会信用代码:91440800249042353U
TEXT;

    $rawFields = $script->parse($text);
    $existingCompanyData = [
        'cid' => 10001681,
        'company' => '会当临绝顶测试公司二六',
        'social_credit_code' => '91440800249042353U',
        'legal' => '陈宁',
        'id_card' => '540102198105309697',
        'mobile' => '13800000000',
        'buyerCompanyType' => 'S',
        'aveInterAmt' => '4000',
        'ECIFCstNo' => '0115652386057386',
    ];

    $merged = $script->mergeWithCompanyData($rawFields, $existingCompanyData);

    expect($merged['company'])->toBe('会当临绝顶测试公司二六')
        ->and($merged['social_credit_code'])->toBe('91440800249042353U')
        ->and($merged['legal'])->toBe('陈宁')
        ->and($merged['id_card'])->toBe('540102198105309697')
        ->and($merged['mobile'])->toBe('13800000000')
        ->and($merged['buyer_company_type'])->toBe('S')
        ->and($merged['trade_amount'])->toBe(4000.0)
        ->and($merged['ecif_cst_no'])->toBe('0115652386057386');
});

it('bankofsun 脚本一键执行流转成功并正确落库', function () {
    $token = platformScriptLoginAsAdmin();

    \Illuminate\Support\Facades\Http::fake([
        'http://api.dev.bankofsun.cn/bankofsun/comm2_auto_flow.php' => \Illuminate\Support\Facades\Http::response([
            'status' => 'success',
            'message' => '已成功自动执行阶段一至阶段三流程',
            'data' => [
                'cid' => 10001680,
                'aid' => '1465',
                'comm2_apply_id' => '47',
                'company' => '起起落落测试公司八',
                'social_credit_code' => '9144080021832648A3',
                'guarantee_status_desc' => '已同意担保 (阶段三达成)',
                'company_apply_state_desc' => '递交银行 (阶段二达成)',
            ],
        ], 200),
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/run', [
            'script_key' => \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY,
            'text' => platformScriptBankofsunSampleText(),
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.ordrNo', 'BOSC0000000001')
        ->assertJsonPath('data.applId', '起起落落测试公司八')
        ->assertJsonPath('data.custBankAcctNo', '9144080021832648A3');

    $run = PlatformScriptRun::query()->where('ordr_no', 'BOSC0000000001')->firstOrFail();
    expect($run->status)->toBe('success')
        ->and($run->output)->toContain('已同意担保 (阶段三达成)');
});

it('bankofsun 脚本支持传递 clear_apply_no 并在 payload 中声明清空', function () {
    $token = platformScriptLoginAsAdmin();

    \Illuminate\Support\Facades\Http::fake([
        'http://api.dev.bankofsun.cn/bankofsun/comm2_auto_flow.php' => function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();
            if (($data['action'] ?? '') === 'match_company') {
                return \Illuminate\Support\Facades\Http::response([
                    'status' => 'success',
                    'matched' => true,
                    'message' => '成功匹配到企业档案',
                    'data' => [
                        'cid' => 10001681,
                        'company' => '起起落落测试公司八',
                        'social_credit_code' => '9144080021832648A3',
                        'has_apply_no' => true,
                        'apply_no' => 'MCPZJSYBFR5273409791677636608',
                    ],
                ], 200);
            }

            expect($data['clear_apply_no'] ?? false)->toBeTrue();

            return \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'message' => '已成功自动执行阶段一至阶段三流程',
                'data' => [
                    'cid' => 10001681,
                    'aid' => '1466',
                    'comm2_apply_id' => '48',
                    'cleared_apply_no' => true,
                    'apply_no' => null,
                    'guarantee_status_desc' => '已同意担保 (阶段三达成)',
                    'company_apply_state_desc' => '递交银行 (阶段二达成)',
                ],
            ], 200);
        },
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/run', [
            'script_key' => \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY,
            'text' => platformScriptBankofsunSampleText(),
            'clear_apply_no' => true,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'success');

    $run = PlatformScriptRun::query()->where('ordr_no', 'BOSC0000000001')->firstOrFail();
    expect(json_decode($run->request_data, true)['clear_apply_no'])->toBeTrue();
});

it('bankofsun 脚本能从整段企业文本中提取统一社会信用代码', function () {
    $script = new \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript(
        config('platform-script.scripts.' . \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY)
    );

    expect($script->parseSocialCreditCode(platformScriptBankofsunSampleText()))
        ->toBe('9144080021832648A3');
});

it('bankofsun 脚本能从只粘贴信用代码的裸文本中提取统一社会信用代码', function () {
    $script = new \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript(
        config('platform-script.scripts.' . \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY)
    );

    expect($script->parseSocialCreditCode("  9144080021832645XT \n"))
        ->toBe('9144080021832645XT');
});

it('bankofsun 脚本取不到统一社会信用代码时抛出异常', function () {
    $script = new \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript(
        config('platform-script.scripts.' . \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY)
    );

    expect(fn () => $script->parseSocialCreditCode('企业名称: 没有信用代码的公司'))
        ->toThrow(InvalidArgumentException::class);
});

it('bankofsun 授信进度查询返回进度快照与可选流水号', function () {
    $token = platformScriptLoginAsAdmin();

    platformScriptCreateRun([
        'script_key' => \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY,
        'ordr_no' => 'BOSC0000000009',
        'appl_id' => '起起落落测试公司八',
        'cust_bank_acct_no' => '9144080021832648A3',
        'request_data' => json_encode(['action' => 'auto_all']),
    ]);

    \Illuminate\Support\Facades\Http::fake([
        'http://api.dev.bankofsun.cn/bankofsun/comm2_auto_flow.php' => \Illuminate\Support\Facades\Http::response([
            'status' => 'success',
            'matched' => true,
            'message' => '已获取授信进度',
            'data' => [
                'cid' => 10001680,
                'aid' => 1465,
                'comm2_apply_id' => 47,
                'company' => '起起落落测试公司八',
                'social_credit_code' => '9144080021832648A3',
                'apply_no' => 'MCPZJSYBFR5292302029966729216',
                'guarantee_status' => 1,
                'approved_status' => '01',
                'signed_status' => '01',
                'agreed_status' => '2',
                'credit_line' => 10000000,
                'amount' => 600000,
                'amount_enough' => false,
                'has_sign_result' => true,
                'can_confirm' => true,
                'block_reason' => '授信申请额度(600000)小于银行授信额度(10000000)',
            ],
        ], 200),
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/progress', [
            'script_key' => \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY,
            'text' => platformScriptBankofsunSampleText(),
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.social_credit_code', '9144080021832648A3')
        ->assertJsonPath('data.matched', true)
        ->assertJsonPath('data.progress.can_confirm', true)
        ->assertJsonPath('data.progress.amount_enough', false)
        ->assertJsonPath('data.ordr_no_candidates.0.ordr_no', 'BOSC0000000009')
        ->assertJsonPath('data.next_ordr_no', 'BOSC0000000010');
});

it('bankofsun 确认担保推送成功并复用指定流水号落库', function () {
    $token = platformScriptLoginAsAdmin();

    \Illuminate\Support\Facades\Http::fake([
        'http://api.dev.bankofsun.cn/bankofsun/comm2_auto_flow.php' => \Illuminate\Support\Facades\Http::response([
            'status' => 'success',
            'message' => '确认担保推送成功',
            'data' => [
                'cid' => 10001680,
                'aid' => 1465,
                'comm2_apply_id' => 47,
                'company' => '起起落落测试公司八',
                'social_credit_code' => '9144080021832648A3',
                'apply_no' => 'MCPZJSYBFR5292302029966729216',
                'contract_no' => 'HT20260831001',
                'skipped_amount_check' => true,
                'agreed_status' => '0',
            ],
        ], 200),
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/confirm-guarantee', [
            'script_key' => \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY,
            'text' => '9144080021832648A3',
            'ordr_no' => 'BOSC0000000009',
            'skip_amount_check' => true,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.ordrNo', 'BOSC0000000009');

    $run = PlatformScriptRun::query()->where('ordr_no', 'BOSC0000000009')->firstOrFail();
    expect(json_decode($run->request_data, true)['action'])->toBe('confirm_guarantee')
        ->and($run->cust_bank_acct_no)->toBe('9144080021832648A3')
        ->and($run->cntrct_no)->toBe('HT20260831001');
});

it('bankofsun 确认担保未指定流水号时新开自增流水号', function () {
    $token = platformScriptLoginAsAdmin();

    \Illuminate\Support\Facades\Http::fake([
        'http://api.dev.bankofsun.cn/bankofsun/comm2_auto_flow.php' => \Illuminate\Support\Facades\Http::response([
            'status' => 'success',
            'message' => '确认担保推送成功',
            'data' => [
                'company' => '起起落落测试公司八',
                'social_credit_code' => '9144080021832648A3',
                'contract_no' => 'HT20260831002',
                'agreed_status' => '0',
            ],
        ], 200),
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/confirm-guarantee', [
            'script_key' => \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY,
            'text' => '9144080021832648A3',
        ]);

    $response->assertOk()->assertJsonPath('data.ordrNo', 'BOSC0000000001');
});

it('bankofsun 确认担保远端拒绝时落成失败状态并带回中文原因', function () {
    $token = platformScriptLoginAsAdmin();

    \Illuminate\Support\Facades\Http::fake([
        'http://api.dev.bankofsun.cn/bankofsun/comm2_auto_flow.php' => \Illuminate\Support\Facades\Http::response([
            'status' => 'error',
            'code' => '10022',
            'message' => '尚未收到交行合同签署通知',
        ], 200),
    ]);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/platform-script/confirm-guarantee', [
            'script_key' => \App\Services\Api\Admin\PlatformScript\Scripts\BankofsunComm2CreditScript::KEY,
            'text' => '9144080021832648A3',
        ]);

    $response->assertOk()->assertJsonPath('data.status', 'failed');

    $run = PlatformScriptRun::query()->where('ordr_no', 'BOSC0000000001')->firstOrFail();
    expect($run->error)->toContain('尚未收到交行合同签署通知');
});




/**
 * sinoloans 放款测试样例粘贴文本。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/7/23
 */
function platformScriptSampleText(): string
{
    return <<<TEXT
申请编号:SYBG5231803687502921728
收款银行账户:6222620110099748528，
合同号: 202901331160999077200000001,
提款金额:10000，
交易对手户名:王瑾诗，
开户网点号: 01310207999，
开户网点名:交通银行上海陕西南路支行
TEXT;
}

/**
 * 创建后台管理员登录令牌。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/7/23
 */
function platformScriptLoginAsAdmin(): string
{
    $admin = User::query()->create([
        'username' => 'platform_script_admin',
        'email' => 'platform-script-admin@example.com',
        'phone' => '13800000021',
        'password' => bcrypt('password'),
        'status' => 1,
    ]);

    $roleId = DB::table('roles')->insertGetId([
        'name' => '超级管理员',
        'code' => 'super',
        'deleted_at' => 0,
    ]);

    DB::table('user_role')->insert([
        'user_id' => $admin->id,
        'role_id' => $roleId,
    ]);

    return auth('api')->login($admin);
}

/**
 * 创建平台脚本执行记录测试数据。
 *
 * @param array $attributes
 * @return PlatformScriptRun
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/7/23
 */
function platformScriptCreateRun(array $attributes = []): PlatformScriptRun
{
    return PlatformScriptRun::query()->create(array_merge([
        'script_key' => SinoloansComm3LoanScript::KEY,
        'ordr_no' => 'SYBG0000000000000056',
        'appl_id' => 'SYBG5231803687502921728',
        'cntrct_no' => '202901331160999077200000001',
        'cntpr_nme' => '王瑾诗',
        'cust_bank_acct_no' => '6222620110099748528',
        'cust_pay_amt' => '10000',
        'actcpe_bchnw_id' => '01310207999',
        'actope_bchnw_nme' => '交通银行上海陕西南路支行',
        'raw_text' => platformScriptSampleText(),
        'request_data' => '{}',
        'output' => '',
        'status' => 'success',
        'error' => null,
        'created_at' => time(),
        'updated_at' => time(),
        'deleted_at' => 0,
    ], $attributes));
}

/**
 * bankofsun 交行企业贷 2.0 样例粘贴文本。
 *
 * @return string
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/8/27
 */
function platformScriptBankofsunSampleText(): string
{
    return <<<TEXT
企业名称: 起起落落测试公司八
统一社会信用代码: 9144080021832648A3
法人姓名: 王五
法人身份证号: 350623198711261343
手机号: 13800000000
企业类型: 贸易型企业
近两年平均交易量: 5000
对公客户号: 0115687030449558
申请额度: 60000000
TEXT;
}

