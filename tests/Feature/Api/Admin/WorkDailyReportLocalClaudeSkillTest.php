<?php

use App\Services\Api\Admin\WorkDailyReportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

function localClaudeSkillCallReportModel(
    WorkDailyReportService $service,
    string $prompt,
    string $model
): string {
    $method = new ReflectionMethod($service, 'callReportModel');
    $method->setAccessible(true);

    return $method->invoke($service, $prompt, $model);
}

it('选择 Claude 导出报表时调用 human-writing skill', function () {
    config(['services.local_claude.bridge_url' => 'http://claude-bridge.test']);

    Http::fake([
        'http://claude-bridge.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '# 测试报表',
                ],
            ]],
        ]),
    ]);

    $content = localClaudeSkillCallReportModel(
        app(WorkDailyReportService::class),
        '原始报表提示词',
        'local-claude/claude-opus-5'
    );

    expect($content)->toBe('# 测试报表');

    Http::assertSent(function (Request $request): bool {
        $prompt = $request->data()['messages'][1]['content'] ?? '';

        return $request->url() === 'http://claude-bridge.test/v1/chat/completions'
            && $request->data()['model'] === 'local-claude/claude-opus-5'
            && str_contains($prompt, '使用 $human-writing')
            && str_contains($prompt, '原始工作记录是唯一事实来源')
            && str_contains($prompt, '禁止检索、追问或补造材料')
            && str_contains($prompt, '原始报表提示词');
    });
});
