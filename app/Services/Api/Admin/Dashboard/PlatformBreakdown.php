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
                        'cells' => array_fill(0, count($months), 0),
                        'log_cells' => array_fill(0, count($months), 0),
                    ];
                }

                $monthIndex = array_search($month, $months, true);
                if ($monthIndex === false) {
                    continue;
                }

                $platformStats[$platformId]['cells'][$monthIndex] += $platform['words'];
                $platformStats[$platformId]['log_cells'][$monthIndex] += 1;
            }
        }

        $rows = array_values($platformStats);
        usort($rows, function ($left, $right) {
            $wordCompare = array_sum($right['cells']) <=> array_sum($left['cells']);
            if ($wordCompare !== 0) {
                return $wordCompare;
            }

            return array_sum($right['log_cells']) <=> array_sum($left['log_cells']);
        });

        $bucketSource = [];
        foreach ($rows as $row) {
            foreach ($row['cells'] as $words) {
                if ($words > 0) {
                    $bucketSource[] = $words;
                }
            }
        }

        return [
            'months' => $months,
            'buckets' => $this->statsAggregator->resolveBuckets($bucketSource),
            'rows' => $rows,
        ];
    }

    public function buildDist(Collection $logs): array
    {
        $stats = array_values($this->statsAggregator->buildPlatformStats($logs));
        $totalWords = array_sum(array_column($stats, 'words'));

        usort($stats, fn ($a, $b) => $b['words'] <=> $a['words']);

        return array_map(fn ($item) => [
            'name'  => $item['name'],
            'words' => $item['words'],
            'pct'   => $totalWords > 0 ? round($item['words'] * 100 / $totalWords, 1) : 0.0,
        ], $stats);
    }
}
