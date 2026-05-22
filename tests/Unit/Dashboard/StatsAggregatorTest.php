<?php

namespace Tests\Unit\Dashboard;

use App\Models\Admin\WorkDailyLog;
use App\Services\Api\Admin\Dashboard\StatsAggregator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class StatsAggregatorTest extends TestCase
{
    public function test_streak_昨天写过今天还没写_仍算连续(): void
    {
        $service = new StatsAggregator();

        $method = new ReflectionMethod($service, 'calculateStreaks');
        $method->setAccessible(true);
        $today = Carbon::today('Asia/Shanghai')->toDateString();
        $yesterday = Carbon::today('Asia/Shanghai')->subDay()->toDateString();

        [$currentStreak] = $method->invoke($service, [$yesterday], $today);

        $this->assertSame(1, $currentStreak);
    }

    public function test_streak_连续两天写到昨天_当前连续为_2(): void
    {
        $service = new StatsAggregator();

        $method = new ReflectionMethod($service, 'calculateStreaks');
        $method->setAccessible(true);
        $today = Carbon::today('Asia/Shanghai');
        $yesterday = $today->copy()->subDay()->toDateString();
        $dayBefore = $today->copy()->subDays(2)->toDateString();

        [$currentStreak] = $method->invoke($service, [$dayBefore, $yesterday], $today->toDateString());

        $this->assertSame(2, $currentStreak);
    }

    public function test_streak_最后写作早于昨天_当前连续归零(): void
    {
        $service = new StatsAggregator();

        $method = new ReflectionMethod($service, 'calculateStreaks');
        $method->setAccessible(true);
        $today = Carbon::today('Asia/Shanghai');
        $twoDaysAgo = $today->copy()->subDays(2)->toDateString();
        $threeDaysAgo = $today->copy()->subDays(3)->toDateString();

        [$currentStreak] = $method->invoke($service, [$threeDaysAgo, $twoDaysAgo], $today->toDateString());

        $this->assertSame(0, $currentStreak);
    }

    public function test_streak_跨月连续能正确计算(): void
    {
        $service = new StatsAggregator();
        $method = new ReflectionMethod($service, 'calculateStreaks');
        $method->setAccessible(true);

        $dates = ['2026-04-29', '2026-04-30', '2026-05-01', '2026-05-02'];
        [$currentStreak, $longestStreak, $range] = $method->invoke($service, $dates, '2026-05-02');

        $this->assertSame(4, $currentStreak);
        $this->assertSame(4, $longestStreak);
        $this->assertSame('2026-04-29', $range['start']);
        $this->assertSame('2026-05-02', $range['end']);
    }

    public function test_favorite_platform_空数据返回_null(): void
    {
        $service = new StatsAggregator();
        $method = new ReflectionMethod($service, 'resolveFavoritePlatform');
        $method->setAccessible(true);

        $result = $method->invoke($service, []);

        $this->assertNull($result);
    }

    public function test_peak_hour_period_深夜映射为_night(): void
    {
        $service = new StatsAggregator();
        $logs = new Collection([
            $this->makeLog('2026-05-10', 2, '夜间输出'),
        ]);
        $method = new ReflectionMethod($service, 'calculatePeakHour');
        $method->setAccessible(true);

        $peak = $method->invoke($service, $logs);

        $this->assertSame('night', $peak['period']);
    }

    private function makeLog(string $date, int $hour, string $content): WorkDailyLog
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
}
