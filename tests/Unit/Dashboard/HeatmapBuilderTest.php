<?php

namespace Tests\Unit\Dashboard;

use App\Models\Admin\WorkDailyLog;
use App\Services\Api\Admin\Dashboard\HeatmapBuilder;
use App\Services\Api\Admin\Dashboard\StatsAggregator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class HeatmapBuilderTest extends TestCase
{
    public function test_热力图数据不足五天时使用固定分桶(): void
    {
        $stats = new StatsAggregator();
        $builder = new HeatmapBuilder($stats);

        $logs = new Collection([
            $this->makeLog('2026-05-09', 9, '一二三四五六七'),
            $this->makeLog('2026-05-10', 10, '一二三四五六七八九'),
        ]);

        $result = $builder->build($logs, [
            'start' => '2026-05-09',
            'end' => '2026-05-11',
        ]);

        $this->assertSame([0, 200, 800, 2000], $result['buckets']);
        $this->assertCount(3, $result['cells']);
        $this->assertSame('2026-05-11', $result['cells'][2]['date']);
    }

    public function test_30日趋势输出日期和字数(): void
    {
        $stats = new StatsAggregator();
        $builder = new HeatmapBuilder($stats);

        $logs = new Collection([
            $this->makeLog('2026-05-01', 9, 'abc'),
            $this->makeLog('2026-05-03', 9, 'abcd'),
        ]);

        $trend = $builder->buildTrend($logs, [
            'start' => '2026-05-01',
            'end' => '2026-05-03',
        ]);

        $this->assertCount(3, $trend);
        $this->assertSame('2026-05-01', $trend[0]['date']);
        $this->assertSame(3, $trend[0]['words']);
        $this->assertSame(0, $trend[1]['words']);
        $this->assertSame(4, $trend[2]['words']);
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
