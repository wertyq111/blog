<?php

namespace App\Services\Api\Admin\Dashboard;

use Illuminate\Support\Collection;

class HeatmapBuilder
{
    public function __construct(
        private readonly StatsAggregator $statsAggregator,
    ) {
    }

    public function build(Collection $logs, array $window): array
    {
        $daily = $this->statsAggregator->buildDailyMap($logs);
        $dates = $this->statsAggregator->buildDateRange($window['start'], $window['end']);
        $cells = [];
        $nonZeroWords = [];

        foreach ($dates as $date) {
            $words = (int) ($daily[$date]['words'] ?? 0);
            $logCount = (int) ($daily[$date]['logs'] ?? 0);
            $cells[] = [
                'date' => $date,
                'words' => $words,
                'logs' => $logCount,
            ];

            if ($words > 0) {
                $nonZeroWords[] = $words;
            }
        }

        return [
            'buckets' => $this->statsAggregator->resolveBuckets($nonZeroWords),
            'cells' => $cells,
        ];
    }

    public function buildTrend(Collection $logs, array $window): array
    {
        $daily = $this->statsAggregator->buildDailyMap($logs);
        $trend = [];

        foreach ($this->statsAggregator->buildDateRange($window['start'], $window['end']) as $date) {
            $trend[] = [
                'date' => $date,
                'words' => (int) ($daily[$date]['words'] ?? 0),
            ];
        }

        return $trend;
    }
}
