<?php

use App\Services\Api\Admin\WorkDailyReportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

function localAgySkillCallReportModel(
    WorkDailyReportService $service,
    string $prompt,
    string $model
): string {
    $method = new ReflectionMethod($service, 'callReportModel');
    $method->setAccessible(true);

    return $method->invoke($service, $prompt, $model);
}

it('选择 Gemini (Agy) 导出报表时调用 human-writing skill', function () {
    config(['services.local_agy.bridge_url' => 'http://agy-bridge.test']);

    Http::fake([
        'http://agy-bridge.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '# 测试报表',
                ],
            ]],
        ]),
    ]);

    $content = localAgySkillCallReportModel(
        app(WorkDailyReportService::class),
        '原始报表提示词',
        'local-agy/gemini-3.6-flash-high'
    );

    expect($content)->toBe('# 测试报表');

    Http::assertSent(function (Request $request): bool {
        $prompt = $request->data()['messages'][1]['content'] ?? '';

        return $request->url() === 'http://agy-bridge.test/v1/chat/completions'
            && $request->data()['model'] === 'local-agy/gemini-3.6-flash-high'
            && str_contains($prompt, '使用 $human-writing')
            && str_contains($prompt, '原始工作记录是唯一事实来源')
            && str_contains($prompt, '禁止检索、追问或补造材料')
            && str_contains($prompt, '原始报表提示词');
    });
});
