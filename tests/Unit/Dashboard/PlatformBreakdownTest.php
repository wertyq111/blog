<?php

namespace Tests\Unit\Dashboard;

use App\Models\Admin\WorkDailyLog;
use App\Services\Api\Admin\Dashboard\PlatformBreakdown;
use App\Services\Api\Admin\Dashboard\StatsAggregator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PlatformBreakdownTest extends TestCase
{
    public function test_平台排行按字数倒序并带占比(): void
    {
        $stats = new StatsAggregator();
        $service = new PlatformBreakdown($stats);

        $logs = new Collection([
            $this->makeLog('2026-05-01', 9, 1, 'Alpha', 'abcdef'),
            $this->makeLog('2026-05-02', 9, 2, 'Beta', 'abc'),
            $this->makeLog('2026-05-03', 9, 1, 'Alpha', 'abcd'),
        ]);

        $rank = $service->buildRank($logs);

        $this->assertSame('Alpha', $rank[0]['name']);
        $this->assertSame(1, $rank[0]['rank']);
        $this->assertGreaterThan($rank[1]['percent'], $rank[0]['percent']);
    }

    public function test_月度矩阵输出12个月和平台行(): void
    {
        $stats = new StatsAggregator();
        $service = new PlatformBreakdown($stats);

        $start = Carbon::today('Asia/Shanghai')->subMonths(11)->startOfMonth();
        $logs = new Collection([
            $this->makeLog($start->copy()->format('Y-m-d'), 10, 1, 'Alpha', 'abc'),
            $this->makeLog($start->copy()->addMonths(1)->format('Y-m-d'), 10, 2, 'Beta', 'abcdef'),
        ]);

        $matrix = $service->buildMatrix($logs);

        $this->assertCount(12, $matrix['months']);
        $this->assertNotEmpty($matrix['rows']);
        $this->assertArrayHasKey('buckets', $matrix);
        $this->assertCount(12, $matrix['rows'][0]['cells']);
        $this->assertIsInt($matrix['rows'][0]['cells'][0]);
        $this->assertCount(12, $matrix['rows'][0]['log_cells']);
    }

    private function makeLog(string $date, int $hour, int $platformId, string $platformName, string $content): WorkDailyLog
    {
        $timestamp = Carbon::parse($date, 'Asia/Shanghai')->setHour($hour)->timestamp;
        $payload = [
            'platforms' => [[
                'platform_id' => $platformId,
                'platform_name' => $platformName,
                'content' => $content,
            ]],
        ];

        $log = new WorkDailyLog();
        $log->setRawAttributes([
            'platform_id' => $platformId,
            'log_date' => $date,
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => $timestamp,
        ], true);

        return $log;
    }
}
