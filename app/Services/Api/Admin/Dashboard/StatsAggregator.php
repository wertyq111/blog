<?php

namespace App\Services\Api\Admin\Dashboard;

use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkDoc;
use App\Models\Admin\WorkPlatform;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StatsAggregator
{
    private array $platformNameCache = [];

    public function firstLogDate(int $userId, bool $isManager): ?string
    {
        return WorkDailyLog::query()
            ->when(!$isManager, function ($query) use ($userId) {
                $query->where('create_user', $userId);
            })
            ->orderBy('log_date')
            ->value('log_date');
    }

    public function fetchLogsBetween(string $start, string $end, int $userId, bool $isManager): Collection
    {
        return WorkDailyLog::query()
            ->when(!$isManager, function ($query) use ($userId) {
                $query->where('create_user', $userId);
            })
            ->where('log_date', '>=', $start)
            ->where('log_date', '<=', $end)
            ->orderBy('log_date')
            ->orderBy('id')
            ->get();
    }

    public function build(Collection $logs, array $rangeWindow, string $range, int $userId, bool $isManager): array
    {
        $daily = $this->buildDailyMap($logs);
        $platformStats = $this->buildPlatformStats($logs);
        $distinctDates = array_keys($daily);
        sort($distinctDates);

        [$currentStreak, $longestStreak, $longestRange] = $this->calculateStreaks($distinctDates, $rangeWindow['end']);
        $peakHour = $this->calculatePeakHour($logs);
        $docsCount = $this->fetchDocsCount($rangeWindow, $range, $userId, $isManager);
        $favoritePlatform = $this->resolveFavoritePlatform($platformStats);
        $totalWords = array_sum(array_column($daily, 'words'));
        $totalLogs = array_sum(array_column($daily, 'logs'));
        $activeDays = count(array_filter($daily, fn ($item) => $item['logs'] > 0));

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
            'hour_dist' => $this->buildHourDist($logs),
            'week_dist' => $this->buildWeekDist($logs),
        ];
    }

    public function buildDailyMap(Collection $logs): array
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

    public function buildPlatformStats(Collection $logs): array
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

    public function extractLogPlatforms(WorkDailyLog $log): array
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

    public function resolveBuckets(array $values): array
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

    public function buildDateRange(string $start, string $end): array
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

    public function countWords(string $content): int
    {
        $plain = preg_replace('/[#>*_`~\[\]\(\)\-]/u', ' ', $content);
        $plain = preg_replace('/\s+/u', '', (string) $plain);

        return mb_strlen((string) $plain);
    }

    private function countWordsSince(Collection $logs, int $days): int
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

    private function countLogsSince(Collection $logs, int $days): int
    {
        $start = Carbon::today('Asia/Shanghai')->subDays($days - 1)->toDateString();

        return $logs->filter(fn ($log) => $log->log_date >= $start)->count();
    }

    private function countActiveDaysSince(Collection $logs, int $days): int
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

    /**
     * 计算当前连续天数和最长连续天数。
     *
     * @param array $dates
     * @param string $endDate
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function calculateStreaks(array $dates, string $endDate): array
    {
        // 周末（周六、周日）中性跳过：不计入连续口径，也不打断连续
        $workdayDates = array_values(array_filter(
            $dates,
            fn ($date) => !Carbon::parse($date)->isWeekend()
        ));

        if (empty($workdayDates)) {
            return [0, 0, ['start' => null, 'end' => null]];
        }

        $longestStreak = 0;
        $longestRange = ['start' => null, 'end' => null];
        $currentRun = 0;
        $runStart = null;
        $previous = null;

        foreach ($workdayDates as $date) {
            // 相邻判定按“下一个工作日”：周五的下一个工作日是周一，跨周末不断
            if ($previous && $this->nextWorkday($previous) === $date) {
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

        $currentStreak = $this->currentWorkdayStreak($workdayDates, $endDate);

        return [$currentStreak, $longestStreak, $longestRange];
    }

    /**
     * 计算当前连续工作日数。工作日当天未写则归零（保留催写语义），周末则保留上一个工作日的连续。
     *
     * @param array $workdayDates 升序排列、仅含工作日的活跃日期
     * @param string $endDate 基准日（通常为今天）
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/1
     */
    private function currentWorkdayStreak(array $workdayDates, string $endDate): int
    {
        // 基准工作日：今天是工作日则取今天，今天是周末则回看上一个工作日
        $anchor = Carbon::parse($endDate)->isWeekend()
            ? $this->previousWorkday($endDate)
            : $endDate;

        $lastActive = end($workdayDates);
        if ($lastActive !== $anchor) {
            return 0;
        }

        $lookup = array_flip($workdayDates);
        $streak = 1;
        $cursor = $lastActive;
        while (isset($lookup[$prev = $this->previousWorkday($cursor)])) {
            $streak++;
            $cursor = $prev;
        }

        return $streak;
    }

    /**
     * 取指定日期之后的下一个工作日（跳过周六、周日）。
     *
     * @param string $date
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/1
     */
    private function nextWorkday(string $date): string
    {
        $cursor = Carbon::parse($date)->addDay();
        while ($cursor->isWeekend()) {
            $cursor->addDay();
        }

        return $cursor->toDateString();
    }

    /**
     * 取指定日期之前的上一个工作日（跳过周六、周日）。
     *
     * @param string $date
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/1
     */
    private function previousWorkday(string $date): string
    {
        $cursor = Carbon::parse($date)->subDay();
        while ($cursor->isWeekend()) {
            $cursor->subDay();
        }

        return $cursor->toDateString();
    }

    private function calculatePeakHour(Collection $logs): array
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

    private function resolveFavoritePlatform(array $stats): ?array
    {
        if (empty($stats)) {
            return null;
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

    private function fetchDocsCount(array $rangeWindow, string $range, int $userId, bool $isManager): array
    {
        $query = WorkDoc::query();
        if (!$isManager) {
            $query->where('create_user', $userId);
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

    private function quantile(array $values, float $percent): int
    {
        $index = (int) floor((count($values) - 1) * $percent);
        return (int) $values[$index];
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

        return 'night';
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

    public function buildHourDist(Collection $logs): array
    {
        $dist = array_fill(0, 24, 0);

        foreach ($logs as $log) {
            $createdAt = (int) $log->getRawOriginal('created_at');
            if ($createdAt <= 0) {
                continue;
            }
            $hour = (int) gmdate('G', $createdAt + 8 * 3600);
            foreach ($this->extractLogPlatforms($log) as $platform) {
                $dist[$hour] += $platform['words'];
            }
        }

        return $dist;
    }

    public function buildWeekDist(Collection $logs): array
    {
        // index 0=周一 … 6=周日
        $dist = array_fill(0, 7, 0);

        foreach ($logs as $log) {
            $dow = (int) Carbon::parse($log->log_date)->dayOfWeekIso - 1; // 1=Mon→0
            foreach ($this->extractLogPlatforms($log) as $platform) {
                $dist[$dow] += $platform['words'];
            }
        }

        return $dist;
    }
}
