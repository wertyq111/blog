<?php

use App\Services\Api\Admin\WorkDailyReportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

function localCodexSkillCallReportModel(
    WorkDailyReportService $service,
    string $prompt,
    string $model
): string {
    $method = new ReflectionMethod($service, 'callReportModel');
    $method->setAccessible(true);

    return $method->invoke($service, $prompt, $model);
}

it('选择 Codex 导出报表时调用 human-writing skill', function () {
    config(['services.local_codex.bridge_url' => 'http://codex-bridge.test']);

    Http::fake([
        'http://codex-bridge.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '# 测试报表',
                ],
            ]],
        ]),
    ]);

    $content = localCodexSkillCallReportModel(
        app(WorkDailyReportService::class),
        '原始报表提示词',
        'local-codex/codex-cli'
    );

    expect($content)->toBe('# 测试报表');

    Http::assertSent(function (Request $request): bool {
        $prompt = $request->data()['messages'][1]['content'] ?? '';

        return $request->url() === 'http://codex-bridge.test/v1/chat/completions'
            && str_contains($prompt, '使用 $human-writing')
            && str_contains($prompt, '原始工作记录是唯一事实来源')
            && str_contains($prompt, '禁止检索、追问或补造材料')
            && str_contains($prompt, '原始报表提示词');
    });
});
