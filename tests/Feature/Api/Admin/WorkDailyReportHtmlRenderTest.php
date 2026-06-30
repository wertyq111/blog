<?php

use App\Models\Admin\WorkDailyReportExport;
use App\Services\Api\Admin\WorkDailyReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

function htmlRenderMakeExport(string $content, string $fileName = '工作年报_2026.md'): WorkDailyReportExport
{
    $export = new WorkDailyReportExport();
    $export->fill([
        'file_name' => $fileName,
        'content' => $content,
    ]);

    return $export;
}

function htmlRenderBootSqlite(): void
{
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
}

// 报表阅读体验的兜底在 HTML 模板：GFM 表格、任务清单、行内代码必须渲染成真正的
// HTML 元素并带上内嵌阅读样式（版心宽度等），而不是把 Markdown 原文当纯文本吐出去。
test('renderHtml 把 GFM Markdown 渲染成自带阅读样式的 HTML 文档', function () {
    $markdown = "# 牛马日常年报 - 2026\n\n## 🥬 数据速览\n\n"
        . "| 指标 | 数值 |\n| --- | --- |\n| 记录条数 | 130 条 |\n\n"
        . "## 🗺️ 下一阶段计划\n\n- [ ] 继续迁移 `ECharts`\n";

    $html = app(WorkDailyReportService::class)->renderHtml(htmlRenderMakeExport($markdown));

    expect($html)
        ->toContain('<title>工作年报_2026</title>')
        ->toContain('<h1>牛马日常年报 - 2026</h1>')
        ->toContain('<table>')
        ->toContain('<td>130 条</td>')
        ->toContain('type="checkbox"')
        ->toContain('<code>ECharts</code>')
        ->toContain('max-width: 800px');
});

// 报表正文来自外部模型生成，原始 HTML 一律剥离，防止下载的报表文件被注入脚本。
test('renderHtml 剥离 Markdown 里的原始 HTML', function () {
    $html = app(WorkDailyReportService::class)->renderHtml(
        htmlRenderMakeExport("# 标题\n\n<script>alert(1)</script>正文\n")
    );

    expect($html)->not->toContain('<script>alert(1)');
});

// 用户在预览里删减/润色后保存：编辑内容必须持久化到导出记录，且后续渲染以新内容为准，
// 这样再次预览/下载/打印拿到的都是编辑后的版本。
test('updateExportContent 持久化编辑后的 Markdown 并影响后续渲染', function () {
    htmlRenderBootSqlite();
    $service = app(WorkDailyReportService::class);

    $export = new WorkDailyReportExport();
    $export->fill([
        'user_id' => 1,
        'type' => 'year',
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
        'status' => WorkDailyReportExport::STATUS_COMPLETED,
        'file_name' => '工作年报_2026.md',
        'content' => "# 年报\n\n## 概览\n\n原始内容。\n",
    ]);
    $export->save();

    $service->updateExportContent($export, "# 年报\n\n## 概览\n\n删减后的内容。\n");

    $fresh = WorkDailyReportExport::query()->find($export->id);
    expect($fresh->content)->toContain('删减后的内容。')
        ->and($fresh->content)->not->toContain('原始内容。')
        ->and($service->renderHtml($fresh))->toContain('删减后的内容。');
});

// 导出列表删除：软删后默认查询不再返回该记录，列表里就看不到了。
test('删除报表导出记录后默认查询不再返回', function () {
    htmlRenderBootSqlite();

    $export = new WorkDailyReportExport();
    $export->fill([
        'user_id' => 1,
        'type' => 'month',
        'period_start' => '2026-06-01',
        'period_end' => '2026-06-30',
        'status' => WorkDailyReportExport::STATUS_COMPLETED,
        'file_name' => '工作月报_2026-06.md',
        'content' => "# 月报\n",
    ]);
    $export->save();
    $id = $export->id;

    $export->delete();

    expect(WorkDailyReportExport::query()->find($id))->toBeNull();
});
