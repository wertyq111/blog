<?php

use App\Http\Controllers\Api\Admin\WorkDailyLogController;

uses(Tests\TestCase::class);

function workDailyReportModelCatalog(): array
{
    $controller = app(WorkDailyLogController::class);
    $method = new ReflectionMethod($controller, 'fetchOpenClawModels');
    $method->setAccessible(true);

    return $method->invoke($controller, 'github-copilot/gpt-5.2-codex');
}

it('报表模型列表提供三个本机 CLI 各自的可选模型', function () {
    // OpenClaw 已下线，模型下拉只靠本机 CLI 桥支撑，缺任何一项前端子菜单就少一个可选项
    config(['services.openclaw.gateway_url' => null]);

    expect(workDailyReportModelCatalog())->toContain(
        'local-codex/gpt-5.5',
        'local-codex/gpt-5.6-sol',
        'local-codex/gpt-5.6-terra',
        'local-agy/gemini-3.5-flash-high',
        'local-agy/gemini-3.6-flash-high',
        'local-agy/gemini-3.7-flash-high',
        'local-claude/claude-opus-4-6',
        'local-claude/claude-opus-4-8',
        'local-claude/claude-opus-5',
    );
});

it('报表模型列表不再暴露走 CLI 默认模型的通用入口', function () {
    // 通用入口指向的就是 CLI 当前默认模型，与具体版本项重复且看不出选中的是哪个
    config(['services.openclaw.gateway_url' => null]);

    expect(workDailyReportModelCatalog())
        ->not->toContain('local-codex/codex-cli')
        ->not->toContain('local-agy/agy-cli')
        ->not->toContain('local-claude/claude-cli');
});
