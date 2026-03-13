<?php

namespace App\Services\Api\Admin;

use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkPlatform;

class WorkDailyLogService
{
    /**
     * 按日期导入日志，并将同一天的平台内容合并到一条记录里。
     *
     * @param int $userId
     * @param array $entries
     * @return int
     */
    public function importEntries(int $userId, array $entries): int
    {
        if (empty($entries)) {
            return 0;
        }

        $platformMap = WorkPlatform::query()->get()->keyBy('name');
        $grouped = [];

        foreach ($entries as $entry) {
            $date = $entry['date'] ?? ($entry['log_date'] ?? null);
            $platformName = trim((string)($entry['platform'] ?? ($entry['platform_name'] ?? '')));
            $content = trim((string)($entry['content'] ?? ''));
            if (!$date || $platformName === '' || $content === '') {
                continue;
            }

            $platform = $platformMap->get($platformName);
            if (!$platform) {
                $platform = new WorkPlatform();
                $platform->fill([
                    'name' => $platformName,
                    'status' => 1,
                    'sort' => 0,
                ]);
                $platform->edit(false);
                $platformMap->put($platformName, $platform);
            }

            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }

            $grouped[$date] = $this->mergePlatforms($grouped[$date], [[
                'platform_id' => (int)$platform->id,
                'platform_name' => $platform->name,
                'content' => $content,
            ]]);
        }

        foreach ($grouped as $date => $platforms) {
            $this->saveDateLog($userId, $date, array_values($platforms), true);
        }

        return count($grouped);
    }

    /**
     * 将旧的按平台拆分的数据合并成按日期一条。
     *
     * @param int|null $userId
     * @param array $dates
     * @return array
     */
    public function normalizeExistingLogs(?int $userId = null, array $dates = []): array
    {
        $query = WorkDailyLog::query()->with('platform')->orderBy('id');
        if ($userId !== null) {
            $query->where('create_user', $userId);
        }
        if (!empty($dates)) {
            $query->whereIn('log_date', $dates);
        }

        $groups = $query->get()->groupBy(function (WorkDailyLog $log) {
            return $log->create_user . '|' . $log->log_date;
        });

        $groupCount = 0;
        $removedCount = 0;
        $changedCount = 0;

        foreach ($groups as $logs) {
            $platforms = $this->collectPlatformsFromLogs($logs->all());
            if (empty($platforms)) {
                continue;
            }

            $primary = $logs->first();
            $normalizedPlatforms = array_values($platforms);
            $currentPlatforms = $this->collectPlatformsFromLogs([$primary]);
            $needsSave = $logs->count() > 1
                || (int)$primary->platform_id !== 0
                || $this->platformsToJson($currentPlatforms) !== $this->platformsToJson($normalizedPlatforms);

            if (!$needsSave) {
                continue;
            }

            $this->saveDateLog((int)$primary->create_user, $primary->log_date, $normalizedPlatforms, true);
            $groupCount++;
            $removedCount += max(0, $logs->count() - 1);
            $changedCount += $logs->count();
        }

        return [
            'groups' => $groupCount,
            'rows' => $changedCount,
            'removed' => $removedCount,
        ];
    }

    /**
     * 保存某天的日志，并清理同日的重复记录。
     *
     * @param int $userId
     * @param string $date
     * @param array $platforms
     * @param bool $replaceExisting
     * @return \App\Models\Admin\WorkDailyLog
     */
    public function saveDateLog(int $userId, string $date, array $platforms, bool $replaceExisting = true): WorkDailyLog
    {
        $logs = WorkDailyLog::query()
            ->with('platform')
            ->where('create_user', $userId)
            ->where('log_date', $date)
            ->orderBy('id')
            ->get();

        $primary = $logs->first();
        $mergedPlatforms = $replaceExisting ? [] : $this->collectPlatformsFromLogs($logs->all());
        $mergedPlatforms = $this->mergePlatforms($mergedPlatforms, $platforms);
        $now = time();

        if (!$primary) {
            $primary = new WorkDailyLog();
            $primary->create_user = $userId;
            $primary->created_at = $now;
        }

        $primary->platform_id = 0;
        $primary->log_date = $date;
        $primary->content = ['platforms' => array_values($mergedPlatforms)];
        $primary->update_user = $userId;
        $primary->updated_at = $now;
        $primary->save();

        if ($logs->count() > 1) {
            foreach ($logs->slice(1) as $extraLog) {
                $extraLog->delete();
            }
        }

        return $primary;
    }

    /**
     * 从一组日志里提取标准化的平台内容。
     *
     * @param array $logs
     * @return array
     */
    private function collectPlatformsFromLogs(array $logs): array
    {
        $platforms = [];

        foreach ($logs as $log) {
            $content = $log->content;
            if (!is_array($content) || empty($content['platforms']) || !is_array($content['platforms'])) {
                continue;
            }

            foreach ($content['platforms'] as $platform) {
                $normalized = $this->normalizePlatformItem($platform);
                if (!$normalized) {
                    continue;
                }
                $platforms = $this->mergePlatforms($platforms, [$normalized]);
            }
        }

        return $platforms;
    }

    /**
     * 合并平台内容；同平台多段内容会顺序拼接。
     *
     * @param array $basePlatforms
     * @param array $incomingPlatforms
     * @return array
     */
    private function mergePlatforms(array $basePlatforms, array $incomingPlatforms): array
    {
        foreach ($incomingPlatforms as $platform) {
            $normalized = $this->normalizePlatformItem($platform);
            if (!$normalized) {
                continue;
            }

            $key = $this->buildPlatformKey($normalized);
            if (isset($basePlatforms[$key])) {
                $basePlatforms[$key]['content'] = $this->mergeContent(
                    $basePlatforms[$key]['content'] ?? '',
                    $normalized['content'] ?? ''
                );
                if (empty($basePlatforms[$key]['platform_name']) && !empty($normalized['platform_name'])) {
                    $basePlatforms[$key]['platform_name'] = $normalized['platform_name'];
                }
                continue;
            }

            $basePlatforms[$key] = $normalized;
        }

        return $basePlatforms;
    }

    /**
     * 标准化单个平台条目。
     *
     * @param array $platform
     * @return array|null
     */
    private function normalizePlatformItem(array $platform): ?array
    {
        $platformId = (int)($platform['platform_id'] ?? ($platform['platformId'] ?? 0));
        $platformName = trim((string)($platform['platform_name'] ?? ($platform['platformName'] ?? '')));
        $content = trim((string)($platform['content'] ?? ''));

        if ($platformId <= 0 && $platformName === '') {
            return null;
        }
        if ($content === '') {
            return null;
        }

        return [
            'platform_id' => $platformId,
            'platform_name' => $platformName,
            'content' => $content,
        ];
    }

    /**
     * 生成平台唯一键。
     *
     * @param array $platform
     * @return string
     */
    private function buildPlatformKey(array $platform): string
    {
        if (!empty($platform['platform_id'])) {
            return 'id:' . $platform['platform_id'];
        }

        return 'name:' . mb_strtolower($platform['platform_name'] ?? '');
    }

    /**
     * 合并同平台的文本内容。
     *
     * @param string $current
     * @param string $incoming
     * @return string
     */
    private function mergeContent(string $current, string $incoming): string
    {
        $current = trim($current);
        $incoming = trim($incoming);

        if ($current === '') {
            return $incoming;
        }
        if ($incoming === '' || $incoming === $current) {
            return $current;
        }

        return $current . "\n\n" . $incoming;
    }

    /**
     * 统一平台数组的比较格式。
     *
     * @param array $platforms
     * @return string
     */
    private function platformsToJson(array $platforms): string
    {
        return json_encode(array_values($platforms), JSON_UNESCAPED_UNICODE);
    }
}
