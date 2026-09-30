<?php

use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkPlatform;
use App\Services\Api\Admin\WorkDailyReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    Schema::dropAllTables();

    Schema::create('work_platforms', function (Blueprint $table) {
        $table->id();
        $table->string('name', 50);
        $table->string('category', 20)->default('work');
        $table->boolean('status')->default(1);
        $table->smallInteger('sort')->default(0);
        $table->unsignedInteger('create_user')->default(0);
        $table->unsignedInteger('created_at')->default(0);
        $table->unsignedInteger('update_user')->default(0);
        $table->unsignedInteger('updated_at')->default(0);
        $table->unsignedInteger('deleted_at')->default(0);
    });

    config(['services.local_claude.bridge_url' => 'http://claude-bridge.test']);
    $this->service = app(WorkDailyReportService::class);
});

function reportCategoryPlatform(string $name, string $category): WorkPlatform
{
    $platform = new WorkPlatform();
    $platform->fill(['name' => $name, 'category' => $category, 'status' => 1, 'sort' => 0]);
    $platform->save();

    return $platform;
}

function reportCategoryLog(string $date, array $platforms): WorkDailyLog
{
    $log = new WorkDailyLog();
    $log->log_date = $date;
    $log->content = ['platforms' => array_map(fn(array $item) => [
        'platform_id' => $item[0]->id,
        'content' => $item[1],
    ], $platforms)];

    return $log;
}

function reportCategoryInvoke(WorkDailyReportService $service, string $method, array $args): mixed
{
    $reflection = new ReflectionMethod($service, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke($service, ...$args);
}

function reportCategoryFixture(): \Illuminate\Support\Collection
{
    $work = reportCategoryPlatform('生意云', WorkPlatform::CATEGORY_WORK);
    $study = reportCategoryPlatform('agent智能平台', WorkPlatform::CATEGORY_STUDY);
    $personal = reportCategoryPlatform('英语学习App', WorkPlatform::CATEGORY_PERSONAL);

    return collect([
        reportCategoryLog('2026-09-01', [[$work, '修复结算弹窗'], [$study, '学习 LangGraph 状态机']]),
        reportCategoryLog('2026-09-02', [[$personal, '用 React 做单词卡片']]),
    ]);
}

// 报表按大类拆分的前提：每条平台内容都要落到它所属的大类下，一条日志里的多个平台可能分属不同大类。
it('按平台大类拆分同一条日志里的多个平台内容', function () {
    $groups = reportCategoryInvoke($this->service, 'groupRecordsByCategory', [reportCategoryFixture()]);

    expect(array_keys($groups[WorkPlatform::CATEGORY_WORK]))->toBe(['生意云'])
        ->and(array_keys($groups[WorkPlatform::CATEGORY_STUDY]))->toBe(['agent智能平台'])
        ->and($groups[WorkPlatform::CATEGORY_PERSONAL]['英语学习App'][0]['content'])->toBe('用 React 做单词卡片');
});

// 工作报表只写工作和学习，并分块喂给模型；个人内容不能混进工作报表。
it('工作报表的原始记录分工作和学习两块且不含个人内容', function () {
    Http::fake([
        'http://claude-bridge.test/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => '# 牛马日常月报 - 2026-09']]],
        ]),
    ]);
    $groups = reportCategoryInvoke($this->service, 'groupRecordsByCategory', [reportCategoryFixture()]);

    reportCategoryInvoke($this->service, 'buildSummaryMarkdown', [
        '牛马日常月报 - 2026-09', $groups, 'month', 'local-claude/claude-opus-5-5',
    ]);

    Http::assertSent(function (Request $request): bool {
        $prompt = $request->data()['messages'][1]['content'] ?? '';

        return str_contains($prompt, '<skill name="work-daily-report">')
            && str_contains($prompt, "# 工作\n\n## 生意云\n- 2026-09-01: 修复结算弹窗")
            && str_contains($prompt, "# 学习\n\n## agent智能平台\n- 2026-09-01: 学习 LangGraph 状态机")
            && str_contains($prompt, '- work_platforms: 生意云')
            && str_contains($prompt, '- study_platforms: agent智能平台')
            && str_contains($prompt, '- record_count: 2')
            && !str_contains($prompt, '单词卡片');
    });
});

it('只有个人内容时工作报表直接输出暂无记录且不调用模型', function () {
    Http::fake();
    $personal = reportCategoryPlatform('英语学习App', WorkPlatform::CATEGORY_PERSONAL);
    $groups = reportCategoryInvoke($this->service, 'groupRecordsByCategory', [
        collect([reportCategoryLog('2026-09-02', [[$personal, '用 React 做单词卡片']])]),
    ]);

    $markdown = reportCategoryInvoke($this->service, 'buildSummaryMarkdown', [
        '牛马日常月报 - 2026-09', $groups, 'month', 'local-claude/claude-opus-5-5',
    ]);

    expect($markdown)->toBe("# 牛马日常月报 - 2026-09\n\n暂无记录。\n");
    Http::assertNothingSent();
});

// 成长记录只取个人内容，走独立的 skill；能力档案也算事实来源，否则模型会拒绝结合档案做推荐。
it('个人成长记录只用个人内容并注入成长记录 skill', function () {
    Http::fake([
        'http://claude-bridge.test/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => '# 个人成长月记 - 2026-09']]],
        ]),
    ]);
    $groups = reportCategoryInvoke($this->service, 'groupRecordsByCategory', [reportCategoryFixture()]);

    reportCategoryInvoke($this->service, 'buildGrowthMarkdown', [
        '个人成长月记 - 2026-09', $groups[WorkPlatform::CATEGORY_PERSONAL], 'month', 'local-claude/claude-opus-5-5',
    ]);

    Http::assertSent(function (Request $request): bool {
        $prompt = $request->data()['messages'][1]['content'] ?? '';

        return str_contains($prompt, '<skill name="personal-growth-report">')
            && str_contains($prompt, '原始记录和注入的个人能力档案是唯一事实来源')
            && str_contains($prompt, '以 personal-growth-report skill 为准')
            && str_contains($prompt, "## 英语学习App\n- 2026-09-02: 用 React 做单词卡片")
            && !str_contains($prompt, '修复结算弹窗')
            && !str_contains($prompt, 'LangGraph');
    });
});
