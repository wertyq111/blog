<?php

namespace App\Services\Api\Admin\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class PlatformBreakdown
{
    public function __construct(
        private readonly StatsAggregator $statsAggregator,
    ) {
    }

    public function buildRank(Collection $logs): array
    {
        $stats = array_values($this->statsAggregator->buildPlatformStats($logs));
        usort($stats, function ($left, $right) {
            if ($left['words'] === $right['words']) {
                return $right['logs'] <=> $left['logs'];
            }

            return $right['words'] <=> $left['words'];
        });

        $totalWords = array_sum(array_column($stats, 'words'));

        foreach ($stats as $index => &$item) {
            $item['rank'] = $index + 1;
            $item['percent'] = $totalWords > 0 ? round($item['words'] * 100 / $totalWords, 1) : 0.0;
        }
        unset($item);

        return $stats;
    }

    public function buildMatrix(Collection $logs): array
    {
        $months = [];
        $monthStart = Carbon::today('Asia/Shanghai')->subMonths(11)->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $months[] = $monthStart->copy()->addMonths($i)->format('Y-m');
        }

        $platformStats = [];
        foreach ($logs as $log) {
            $month = Carbon::parse($log->log_date)->format('Y-m');
            foreach ($this->statsAggregator->extractLogPlatforms($log) as $platform) {
                $platformId = (int) $platform['platform_id'];
                $platformName = $platform['platform_name'] ?: '未绑定平台';
                if (!isset($platformStats[$platformId])) {
                    $platformStats[$platformId] = [
                        'platform_id' => $platformId,
                        'name' => $platformName,
                        'cells' => array_fill(0, count($months), ['words' => 0, 'logs' => 0]),
                    ];
                }

                $monthIndex = array_search($month, $months, true);
                if ($monthIndex === false) {
                    continue;
                }

                $platformStats[$platformId]['cells'][$monthIndex]['words'] += $platform['words'];
                $platformStats[$platformId]['cells'][$monthIndex]['logs'] += 1;
            }
        }

        $rows = array_values($platformStats);
        usort($rows, function ($left, $right) {
            return array_sum(array_column($right['cells'], 'words')) <=> array_sum(array_column($left['cells'], 'words'));
        });

        $bucketSource = [];
        foreach ($rows as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell['words'] > 0) {
                    $bucketSource[] = $cell['words'];
                }
            }
        }

        return [
            'months' => $months,
            'buckets' => $this->statsAggregator->resolveBuckets($bucketSource),
            'rows' => $rows,
        ];
    }
}
