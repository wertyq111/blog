<?php

use App\Models\Admin\WorkDailyLog;
use App\Services\Api\Admin\Dashboard\StatsAggregator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

it('工作日今天和昨天都写时当前连续为 2', function () {
    // 周一、周二都写，今天周二
    [$currentStreak, $longestStreak] = statsInvokeStreaks(['2026-06-01', '2026-06-02'], '2026-06-02');

    expect($currentStreak)->toBe(2)
        ->and($longestStreak)->toBe(2);
});

it('工作日今天还没写时当前连续归零催写', function () {
    // 写了周一，今天周二还没写 -> 当前连续归零（方案 A 催写语义）
    [$currentStreak] = statsInvokeStreaks(['2026-06-01'], '2026-06-02');

    expect($currentStreak)->toBe(0);
});

it('周末时上一个工作日写过则连续不断签', function () {
    // 今天周六，上周五写过 -> 连续保留为 1，不因周末归零
    [$currentStreak] = statsInvokeStreaks(['2026-05-29'], '2026-05-30');

    expect($currentStreak)->toBe(1);
});

it('跨周末连续工作日的当前与最长均为 4', function () {
    // 周四、周五、（周末休息）、周一、周二，今天周二
    [$currentStreak, $longestStreak, $range] = statsInvokeStreaks(
        ['2026-05-28', '2026-05-29', '2026-06-01', '2026-06-02'],
        '2026-06-02'
    );

    expect($currentStreak)->toBe(4)
        ->and($longestStreak)->toBe(4)
        ->and($range['start'])->toBe('2026-05-28')
        ->and($range['end'])->toBe('2026-06-02');
});

it('周五写过周一漏写则周二当前连续归零', function () {
    // 写了周五，周一漏写，今天周二 -> 断签归零
    [$currentStreak] = statsInvokeStreaks(['2026-05-29'], '2026-06-02');

    expect($currentStreak)->toBe(0);
});

it('只在周末写不形成连续', function () {
    // 仅周六、周日写，工作日没写 -> 当前与最长都为 0（周末中性跳过）
    [$currentStreak, $longestStreak] = statsInvokeStreaks(['2026-05-30', '2026-05-31'], '2026-06-01');

    expect($currentStreak)->toBe(0)
        ->and($longestStreak)->toBe(0);
});

it('跨月连续工作日能正确计算', function () {
    // 周四(4/30)、周五(5/1)、（周末）、周一(5/4)、周二(5/5)
    [$currentStreak, $longestStreak, $range] = statsInvokeStreaks(
        ['2026-04-30', '2026-05-01', '2026-05-04', '2026-05-05'],
        '2026-05-05'
    );

    expect($currentStreak)->toBe(4)
        ->and($longestStreak)->toBe(4)
        ->and($range['start'])->toBe('2026-04-30')
        ->and($range['end'])->toBe('2026-05-05');
});

it('偏好平台空数据返回 null', function () {
    $method = new ReflectionMethod(StatsAggregator::class, 'resolveFavoritePlatform');
    $method->setAccessible(true);

    expect($method->invoke(new StatsAggregator(), []))->toBeNull();
});

it('高产时段深夜映射为 night', function () {
    $logs = new Collection([
        statsMakeLog('2026-05-10', 2, '夜间输出'),
    ]);
    $method = new ReflectionMethod(StatsAggregator::class, 'calculatePeakHour');
    $method->setAccessible(true);

    expect($method->invoke(new StatsAggregator(), $logs)['period'])->toBe('night');
});

/**
 * 以指定的活跃日期与基准日调用私有 calculateStreaks。
 *
 * @param array $dates
 * @param string $endDate
 * @return array
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/6/1
 */
function statsInvokeStreaks(array $dates, string $endDate): array
{
    $method = new ReflectionMethod(StatsAggregator::class, 'calculateStreaks');
    $method->setAccessible(true);

    return $method->invoke(new StatsAggregator(), $dates, $endDate);
}

/**
 * 构造一条用于统计的工作日志模型。
 *
 * @param string $date
 * @param int $hour
 * @param string $content
 * @return WorkDailyLog
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2026/6/1
 */
function statsMakeLog(string $date, int $hour, string $content): WorkDailyLog
{
    $timestamp = Carbon::parse($date, 'Asia/Shanghai')->setHour($hour)->timestamp;
    $payload = [
        'platforms' => [[
            'platform_id' => 1,
            'platform_name' => 'Alpha',
            'content' => $content,
        ]],
    ];

    $log = new WorkDailyLog();
    $log->setRawAttributes([
        'platform_id' => 1,
        'log_date' => $date,
        'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        'created_at' => $timestamp,
    ], true);

    return $log;
}
