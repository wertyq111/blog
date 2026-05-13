<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkDoc;
use App\Models\Admin\WorkPlatform;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private array $platformNameCache = [];

    public function stats(Request $request): JsonResponse
    {
        if (!auth('api')->user()) {
            return response()->json(['code' => 401, 'msg' => 'Unauthenticated.', 'data' => null], 401);
        }

        $view = $request->get('view', 'overview');
        $range = $request->get('range', 'all');

        if (!in_array($view, ['overview', 'platform'], true)) {
            return response()->json(['code' => 422, 'msg' => '参数错误：view', 'data' => null], 422);
        }

        if (!in_array($range, ['all', '30d', '7d'], true)) {
            return response()->json(['code' => 422, 'msg' => '参数错误：range', 'data' => null], 422);
        }

        $overviewWindow = $this->resolveRangeWindow($range);
        $heatmapWindow = [
            'start' => Carbon::today('Asia/Shanghai')->subDays(364)->toDateString(),
            'end' => Carbon::today('Asia/Shanghai')->toDateString(),
        ];
        $trendWindow = [
            'start' => Carbon::today('Asia/Shanghai')->subDays(29)->toDateString(),
            'end' => Carbon::today('Asia/Shanghai')->toDateString(),
        ];

        $response = [
            'view' => $view,
            'range' => $range,
            'generated_at' => time(),
        ];

        if ($view === 'overview') {
            $logs = $this->fetchLogsBetween($overviewWindow['start'], $overviewWindow['end']);
            $response['metrics'] = $this->buildMetrics($logs, $overviewWindow, $range);
            $response['heatmap'] = $this->buildHeatmap(
                $this->fetchLogsBetween($heatmapWindow['start'], $heatmapWindow['end']),
                $heatmapWindow
            );
            $response['trend_30d'] = $this->buildTrend(
                $this->fetchLogsBetween($trendWindow['start'], $trendWindow['end']),
                $trendWindow
            );
        } else {
            $logs = $this->fetchLogsBetween($overviewWindow['start'], $overviewWindow['end']);
            $response['rank'] = $this->buildPlatformRank($logs);
            $response['matrix'] = $this->buildPlatformMatrix(
                $this->fetchLogsBetween(Carbon::today('Asia/Shanghai')->subMonths(11)->startOfMonth()->toDateString(), Carbon::today('Asia/Shanghai')->endOfMonth()->toDateString())
            );
        }

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $response,
        ]);
    }

    private function resolveRangeWindow(string $range): array
    {
        $today = Carbon::today('Asia/Shanghai');

        if ($range === '7d') {
            return [
                'start' => $today->copy()->subDays(6)->toDateString(),
                'end' => $today->toDateString(),
            ];
        }

        if ($range === '30d') {
            return [
                'start' => $today->copy()->subDays(29)->toDateString(),
                'end' => $today->toDateString(),
            ];
        }

        $firstLogDate = WorkDailyLog::query()
            ->when(!$this->isManager(), function ($query) {
                $query->where('create_user', auth('api')->id());
            })
            ->orderBy('log_date')
            ->value('log_date');

        return [
            'start' => $firstLogDate ?: $today->toDateString(),
            'end' => $today->toDateString(),
        ];
    }

    private function fetchLogsBetween(string $start, string $end)
    {
        return WorkDailyLog::query()
            ->when(!$this->isManager(), function ($query) {
                $query->where('create_user', auth('api')->id());
            })
            ->where('log_date', '>=', $start)
            ->where('log_date', '<=', $end)
            ->orderBy('log_date')
            ->orderBy('id')
            ->get();
    }

    private function buildMetrics($logs, array $rangeWindow, string $range): array
    {
        $daily = $this->buildDailyMap($logs);
        $platformStats = $this->buildPlatformStats($logs);
        $distinctDates = array_keys($daily);
        sort($distinctDates);

        [$currentStreak, $longestStreak, $longestRange] = $this->calculateStreaks($distinctDates, $rangeWindow['end']);
        $peakHour = $this->calculatePeakHour($logs);
        $docsCount = $this->fetchDocsCount($rangeWindow, $range);
        $favoritePlatform = $this->resolveFavoritePlatform($platformStats);
        $totalWords = array_sum(array_column($daily, 'words'));
        $totalLogs = array_sum(array_column($daily, 'logs'));
        $activeDays = count(array_filter($daily, function ($item) {
            return $item['logs'] > 0;
        }));

        return [
            'total_words' => [
                'value' => $totalWords,
                'delta_7d' => $this->countWordsSince($logs, 7),
            ],
            'total_logs' => [
                'value' => $totalLogs,
                'delta_7d' => $this->countLogsSince($logs, 7),
            ],
            'total_docs' => [
                'value' => $docsCount['value'],
                'delta_7d' => $docsCount['delta_7d'],
            ],
            'active_days' => [
                'value' => $activeDays,
                'delta_7d' => $this->countActiveDaysSince($logs, 7),
            ],
            'current_streak' => [
                'value' => $currentStreak,
                'hint' => $currentStreak > 0 ? '保持节奏' : '今天还没写呢',
            ],
            'longest_streak' => [
                'value' => $longestStreak,
                'start' => $longestRange['start'],
                'end' => $longestRange['end'],
            ],
            'peak_hour' => $peakHour,
            'favorite_platform' => $favoritePlatform,
        ];
    }

    private function buildHeatmap($logs, array $window): array
    {
        $daily = $this->buildDailyMap($logs);
        $dates = $this->buildDateRange($window['start'], $window['end']);
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
            'buckets' => $this->resolveBuckets($nonZeroWords),
            'cells' => $cells,
        ];
    }

    private function buildTrend($logs, array $window): array
    {
        $daily = $this->buildDailyMap($logs);
        $trend = [];

        foreach ($this->buildDateRange($window['start'], $window['end']) as $date) {
            $trend[] = [
                'date' => $date,
                'words' => (int) ($daily[$date]['words'] ?? 0),
            ];
        }

        return $trend;
    }

    private function buildPlatformRank($logs): array
    {
        $stats = array_values($this->buildPlatformStats($logs));
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

    private function buildPlatformMatrix($logs): array
    {
        $months = [];
        $monthStart = Carbon::today('Asia/Shanghai')->subMonths(11)->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $months[] = $monthStart->copy()->addMonths($i)->format('Y-m');
        }

        $platformStats = [];
        foreach ($logs as $log) {
            $month = Carbon::parse($log->log_date)->format('Y-m');
            foreach ($this->extractLogPlatforms($log) as $platform) {
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
            'buckets' => $this->resolveBuckets($bucketSource),
            'rows' => $rows,
        ];
    }

    private function buildDailyMap($logs): array
    {
        $daily = [];

        foreach ($logs as $log) {
            $date = $log->log_date;
            if (!isset($daily[$date])) {
                $daily[$date] = ['words' => 0, 'logs' => 0];
            }

            $daily[$date]['logs'] += 1;

            foreach ($this->extractLogPlatforms($log) as $platform) {
                $daily[$date]['words'] += $platform['words'];
            }
        }

        return $daily;
    }

    private function buildPlatformStats($logs): array
    {
        $stats = [];

        foreach ($logs as $log) {
            foreach ($this->extractLogPlatforms($log) as $platform) {
                $platformId = (int) $platform['platform_id'];
                if (!isset($stats[$platformId])) {
                    $stats[$platformId] = [
                        'platform_id' => $platformId,
                        'name' => $platform['platform_name'] ?: '未绑定平台',
                        'words' => 0,
                        'logs' => 0,
                    ];
                }

                $stats[$platformId]['words'] += $platform['words'];
                $stats[$platformId]['logs'] += 1;
            }
        }

        return $stats;
    }

    private function extractLogPlatforms(WorkDailyLog $log): array
    {
        $content = $log->content;
        $platforms = [];

        if (is_array($content) && isset($content['platforms']) && is_array($content['platforms'])) {
            $platforms = $content['platforms'];
        } elseif (is_array($content)) {
            $platforms = $content;
        }

        if (empty($platforms)) {
            $platforms = [[
                'platform_id' => $log->platform_id ?: 0,
                'platform_name' => optional($log->platform)->name,
                'content' => is_string($content) ? $content : '',
            ]];
        }

        $normalized = [];
        foreach ($platforms as $platform) {
            if (!is_array($platform)) {
                continue;
            }
            $platformId = (int) ($platform['platform_id'] ?? $platform['platformId'] ?? $log->platform_id ?? 0);
            $platformName = $platform['platform_name'] ?? $platform['platformName'] ?? null;
            if (!$platformName && $platformId > 0) {
                if (!array_key_exists($platformId, $this->platformNameCache)) {
                    $this->platformNameCache[$platformId] = WorkPlatform::query()->whereKey($platformId)->value('name');
                }
                $platformName = $this->platformNameCache[$platformId];
            }
            $contentText = (string) ($platform['content'] ?? '');
            $normalized[] = [
                'platform_id' => $platformId,
                'platform_name' => $platformName ?: '未绑定平台',
                'content' => $contentText,
                'words' => $this->countWords($contentText),
            ];
        }

        return $normalized;
    }

    private function countWords(string $content): int
    {
        $plain = preg_replace('/[#>*_`~\[\]\(\)\-]/u', ' ', $content);
        $plain = preg_replace('/\s+/u', '', (string) $plain);

        return mb_strlen((string) $plain);
    }

    private function countWordsSince($logs, int $days): int
    {
        $start = Carbon::today('Asia/Shanghai')->subDays($days - 1)->toDateString();
        $total = 0;

        foreach ($logs as $log) {
            if ($log->log_date < $start) {
                continue;
            }
            foreach ($this->extractLogPlatforms($log) as $platform) {
                $total += $platform['words'];
            }
        }

        return $total;
    }

    private function countLogsSince($logs, int $days): int
    {
        $start = Carbon::today('Asia/Shanghai')->subDays($days - 1)->toDateString();

        return $logs->filter(function ($log) use ($start) {
            return $log->log_date >= $start;
        })->count();
    }

    private function countActiveDaysSince($logs, int $days): int
    {
        $start = Carbon::today('Asia/Shanghai')->subDays($days - 1)->toDateString();
        $dates = [];

        foreach ($logs as $log) {
            if ($log->log_date >= $start) {
                $dates[$log->log_date] = true;
            }
        }

        return count($dates);
    }

    private function calculateStreaks(array $dates, string $endDate): array
    {
        if (empty($dates)) {
            return [0, 0, ['start' => null, 'end' => null]];
        }

        $currentStreak = 0;
        $longestStreak = 0;
        $longestRange = ['start' => null, 'end' => null];
        $currentRun = 0;
        $runStart = null;
        $previous = null;

        foreach ($dates as $date) {
            if ($previous && Carbon::parse($previous)->addDay()->toDateString() === $date) {
                $currentRun++;
            } else {
                $currentRun = 1;
                $runStart = $date;
            }

            if ($currentRun > $longestStreak) {
                $longestStreak = $currentRun;
                $longestRange = ['start' => $runStart, 'end' => $date];
            }

            $previous = $date;
        }

        $lastDate = end($dates);
        if ($lastDate === $endDate || $lastDate === Carbon::parse($endDate)->subDay()->toDateString()) {
            $currentStreak = 1;
            $currentDate = Carbon::parse($lastDate);
            while (in_array($currentDate->copy()->subDay()->toDateString(), $dates, true)) {
                $currentStreak++;
                $currentDate->subDay();
            }
        }

        return [$currentStreak, $longestStreak, $longestRange];
    }

    private function calculatePeakHour($logs): array
    {
        $hourCounts = [];

        foreach ($logs as $log) {
            $createdAt = (int) $log->getRawOriginal('created_at');
            if ($createdAt <= 0) {
                continue;
            }
            $hour = (int) gmdate('G', $createdAt + 8 * 3600);
            $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
        }

        if (empty($hourCounts)) {
            return [
                'hour' => null,
                'period' => null,
                'label' => '—',
            ];
        }

        arsort($hourCounts);
        $hour = (int) array_key_first($hourCounts);

        return [
            'hour' => $hour,
            'period' => $this->resolvePeriod($hour),
            'label' => $this->resolvePeriodLabel($hour),
        ];
    }

    private function resolveFavoritePlatform(array $stats): array
    {
        if (empty($stats)) {
            return [
                'platform_id' => null,
                'name' => '—',
                'words' => 0,
                'logs' => 0,
                'percent' => 0.0,
            ];
        }

        usort($stats, function ($left, $right) {
            if ($left['words'] === $right['words']) {
                return $right['logs'] <=> $left['logs'];
            }
            return $right['words'] <=> $left['words'];
        });

        $favorite = $stats[0];
        $totalWords = array_sum(array_column($stats, 'words'));

        return [
            'platform_id' => $favorite['platform_id'],
            'name' => $favorite['name'],
            'words' => $favorite['words'],
            'logs' => $favorite['logs'],
            'percent' => $totalWords > 0 ? round($favorite['words'] * 100 / $totalWords, 1) : 0.0,
        ];
    }

    private function fetchDocsCount(array $rangeWindow, string $range): array
    {
        $query = WorkDoc::query();
        if (!$this->isManager()) {
            $query->where('create_user', auth('api')->id());
        }

        $valueQuery = clone $query;
        if ($range !== 'all') {
            $valueQuery
                ->where('updated_at', '>=', Carbon::parse($rangeWindow['start'])->startOfDay()->timestamp)
                ->where('updated_at', '<=', Carbon::parse($rangeWindow['end'])->endOfDay()->timestamp);
        }
        $value = $valueQuery->count();

        $delta7d = (clone $query)
            ->where('updated_at', '>=', Carbon::today('Asia/Shanghai')->subDays(6)->startOfDay()->timestamp)
            ->count();

        return [
            'value' => $value,
            'delta_7d' => $delta7d,
        ];
    }

    private function resolveBuckets(array $values): array
    {
        sort($values);
        if (count($values) < 5) {
            return [0, 200, 800, 2000];
        }

        return [
            0,
            $this->quantile($values, 0.5),
            $this->quantile($values, 0.75),
            $this->quantile($values, 0.9),
        ];
    }

    private function quantile(array $values, float $percent): int
    {
        $index = (int) floor((count($values) - 1) * $percent);
        return (int) $values[$index];
    }

    private function buildDateRange(string $start, string $end): array
    {
        $dates = [];
        $current = Carbon::parse($start);
        $target = Carbon::parse($end);

        while ($current->lte($target)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        return $dates;
    }

    private function resolvePeriod(int $hour): string
    {
        if ($hour > 5 && $hour <= 11) {
            return 'morning';
        }
        if ($hour > 11 && $hour <= 13) {
            return 'noon';
        }
        if ($hour > 13 && $hour <= 18) {
            return 'afternoon';
        }
        if ($hour > 18 && $hour <= 22) {
            return 'evening';
        }

        return 'late_night';
    }

    private function resolvePeriodLabel(int $hour): string
    {
        return match ($this->resolvePeriod($hour)) {
            'morning' => '早晨',
            'noon' => '中午',
            'afternoon' => '下午',
            'evening' => '晚上',
            default => '深夜',
        };
    }

    private function isManager(): bool
    {
        $user = auth('api')->user();
        if (!$user) {
            return false;
        }

        foreach ($user->roles as $role) {
            if ($role->code === 'super') {
                return true;
            }
        }

        return false;
    }
}
