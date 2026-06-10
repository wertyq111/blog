<?php

use App\Models\Admin\WorkDailyReportExport;
use App\Services\Api\Admin\WorkDailyReportService;
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

    Schema::create('work_daily_report_exports', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('user_id');
        $table->string('type', 20);
        $table->date('period_start');
        $table->date('period_end');
        $table->string('model', 120)->nullable();
        $table->string('status', 20);
        $table->string('file_name', 255);
        $table->longText('content')->nullable();
        $table->text('error_message')->nullable();
        $table->unsignedInteger('started_at')->default(0);
        $table->unsignedInteger('finished_at')->default(0);
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });

    $this->service = new WorkDailyReportService();
});

function makeExport(array $attributes): WorkDailyReportExport
{
    $export = new WorkDailyReportExport();
    $export->fill(array_merge([
        'user_id' => 1,
        'type' => 'month',
        'model' => null,
        'status' => WorkDailyReportExport::STATUS_COMPLETED,
        'file_name' => '工作月报.md',
        'content' => "# 牛马日常月报\n\n## 概览\n\n上期概览内容。\n\n## 平台总结\n\n- 略\n",
    ], $attributes));
    $export->save();

    return $export;
}

// 环比参考必须来自"上一个已完成的同类型周期"——失败导出、其他类型、未结束于当期之前的周期都不能入选，
// 否则报表会引用错误周期的内容做趋势对比。
test('findPreviousOverview 选中 period_end 最近且已完成的同类型上期报表', function () {
    makeExport([
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
        'content' => "# 三月\n\n## 概览\n\n三月概览。\n",
    ]);
    makeExport([
        'period_start' => '2026-04-01',
        'period_end' => '2026-04-30',
        'content' => "# 四月\n\n## 概览\n\n四月概览。\n",
    ]);
    // 更近但失败的导出不能当参考
    makeExport([
        'period_start' => '2026-05-01',
        'period_end' => '2026-05-31',
        'status' => WorkDailyReportExport::STATUS_FAILED,
        'content' => null,
    ]);
    // 类型不同的不能当参考
    makeExport([
        'type' => 'week',
        'period_start' => '2026-05-25',
        'period_end' => '2026-05-31',
        'content' => "# 周报\n\n## 概览\n\n周报概览。\n",
    ]);

    $overview = $this->service->findPreviousOverview(1, 'month', '2026-06-01');

    expect($overview)->toBe('四月概览。');
});

// 没有可靠上期时必须返回 null，让报表完全不写环比——宁缺毋滥，防止模型编造趋势。
test('findPreviousOverview 无已完成上期时返回 null', function () {
    makeExport([
        'period_start' => '2026-05-01',
        'period_end' => '2026-05-31',
        'status' => WorkDailyReportExport::STATUS_FAILED,
        'content' => null,
    ]);
    // 当期及未来的周期不算"上期"
    makeExport([
        'period_start' => '2026-06-01',
        'period_end' => '2026-06-30',
        'content' => "# 六月\n\n## 概览\n\n六月概览。\n",
    ]);

    expect($this->service->findPreviousOverview(1, 'month', '2026-06-01'))->toBeNull();
});

// 升级到带图标的新版结构后，历史报表仍是无图标旧格式；两种格式都要能解析，
// 且超长概览要截断，避免撑爆下游 prompt。
test('extractOverview 兼容新旧标题格式并截断超长内容', function () {
    $legacy = "# 报表\n\n## 概览\n\n旧格式概览。\n\n## 平台总结\n\n- 略\n";
    $iconized = "# 报表\n\n## 🏝️ 概览\n\n新格式概览。\n\n## 🥬 数据速览\n\n| 指标 | 数值 |\n";
    $long = "# 报表\n\n## 概览\n\n" . str_repeat('长', 700) . "\n";

    expect($this->service->extractOverview($legacy))->toBe('旧格式概览。')
        ->and($this->service->extractOverview($iconized))->toBe('新格式概览。')
        ->and(mb_strlen((string)$this->service->extractOverview($long)))->toBe(600)
        ->and($this->service->extractOverview("# 报表\n\n没有概览节。\n"))->toBeNull();
});
