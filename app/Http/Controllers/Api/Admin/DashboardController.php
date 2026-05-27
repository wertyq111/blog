<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\DashboardStatsRequest;
use App\Models\Admin\WorkDailyLog;
use App\Services\Api\Admin\Dashboard\DashboardCache;
use Illuminate\Support\Facades\DB;
use App\Services\Api\Admin\Dashboard\HeatmapBuilder;
use App\Services\Api\Admin\Dashboard\PlatformBreakdown;
use App\Services\Api\Admin\Dashboard\StatsAggregator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * 初始化工作台统计依赖。
     *
     * @param DashboardCache $dashboardCache
     * @param StatsAggregator $statsAggregator
     * @param HeatmapBuilder $heatmapBuilder
     * @param PlatformBreakdown $platformBreakdown
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function __construct(
        private readonly DashboardCache $dashboardCache,
        private readonly StatsAggregator $statsAggregator,
        private readonly HeatmapBuilder $heatmapBuilder,
        private readonly PlatformBreakdown $platformBreakdown,
    ) {
    }

    /**
     * 获取工作台统计。
     *
     * @param DashboardStatsRequest $request
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function stats(DashboardStatsRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['code' => 401, 'msg' => 'Unauthenticated.', 'data' => null], 401);
        }

        $view = $request->get('view', 'overview');
        $range = $request->get('range', 'all');

        $isManager = $this->isManager($user);
        [$response, $cacheHit] = $this->dashboardCache->remember($user->id, $isManager, $view, $range, function () use ($user, $isManager, $view, $range) {
            return $this->buildResponse((int) $user->id, $isManager, $view, $range);
        });
        $response['cache_hit'] = $cacheHit;

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $response,
        ]);
    }

    private function buildResponse(int $userId, bool $isManager, string $view, string $range): array
    {
        $overviewWindow = $this->resolveRangeWindow($range, $userId, $isManager);
        $heatmapWindow = [
            'start' => Carbon::today('Asia/Shanghai')->subDays(364)->toDateString(),
            'end' => Carbon::today('Asia/Shanghai')->toDateString(),
        ];
        $trendWindow = [
            'start' => Carbon::today('Asia/Shanghai')->subDays(29)->toDateString(),
            'end' => Carbon::today('Asia/Shanghai')->toDateString(),
        ];

        // 所有 view 共通：当前窗口的日志一次拿到，组装共通基础数据，避免切 tab 时 KPI/热力/趋势/最近 5 条日志/标签 直接清空。
        $overviewLogs = $this->statsAggregator->fetchLogsBetween($overviewWindow['start'], $overviewWindow['end'], $userId, $isManager);
        $allLogIds = $overviewLogs->pluck('id')->all();

        $recentLogs = WorkDailyLog::query()
            ->with('tags:id,name')
            ->when(!$isManager, fn ($q) => $q->where('create_user', $userId))
            ->orderBy('log_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $response = [
            'view' => $view,
            'range' => $range,
            'generated_at' => time(),
            'metrics' => $this->statsAggregator->build($overviewLogs, $overviewWindow, $range, $userId, $isManager),
            'heatmap' => $this->heatmapBuilder->build(
                $this->statsAggregator->fetchLogsBetween($heatmapWindow['start'], $heatmapWindow['end'], $userId, $isManager),
                $heatmapWindow
            ),
            'trend_30d' => $this->heatmapBuilder->buildTrend(
                $this->statsAggregator->fetchLogsBetween($trendWindow['start'], $trendWindow['end'], $userId, $isManager),
                $trendWindow
            ),
            'platform_dist' => $this->platformBreakdown->buildDist($overviewLogs),
            'recent_logs' => $recentLogs
                ->map(fn ($log) => [
                    'id'          => $log->id,
                    'log_date'    => $log->log_date,
                    'content'     => $log->content,
                    'create_time' => $log->getRawOriginal('created_at'),
                    'tags'        => $log->tags->pluck('name')->all(),
                ])
                ->values()
                ->all(),
            'tag_ranking' => $this->buildTagRanking($allLogIds),
        ];

        if ($view === 'overview') {
            return $response;
        }

        if ($view === 'platform') {
            $matrixWindow = [
                'start' => Carbon::today('Asia/Shanghai')->subMonths(11)->startOfMonth()->toDateString(),
                'end' => Carbon::today('Asia/Shanghai')->endOfMonth()->toDateString(),
            ];
            $response['rank'] = $this->platformBreakdown->buildRank($overviewLogs);
            $response['matrix'] = $this->platformBreakdown->buildMatrix(
                $this->statsAggregator->fetchLogsBetween($matrixWindow['start'], $matrixWindow['end'], $userId, $isManager)
            );

            return $response;
        }

        if ($view === 'hour') {
            // metrics 里已经有 hour_dist / week_dist；保留顶层是为了 hour 视图组件可能依赖的旧路径，兼容前端两种读取方式。
            $response['hour_dist'] = $this->statsAggregator->buildHourDist($overviewLogs);
            $response['week_dist'] = $this->statsAggregator->buildWeekDist($overviewLogs);

            return $response;
        }

        // view === 'tag'：tag_ranking 已在共通基础里
        return $response;
    }

    private function resolveRangeWindow(string $range, int $userId, bool $isManager): array
    {
        $today = Carbon::today('Asia/Shanghai');

        if ($range === '7d') {
            return [
                'start' => $today->copy()->subDays(6)->toDateString(),
                'end' => $today->toDateString(),
            ];
        }

        if ($range === 'today') {
            return [
                'start' => $today->toDateString(),
                'end' => $today->toDateString(),
            ];
        }

        if ($range === '30d') {
            return [
                'start' => $today->copy()->subDays(29)->toDateString(),
                'end' => $today->toDateString(),
            ];
        }

        $firstLogDate = $this->statsAggregator->firstLogDate($userId, $isManager);

        return [
            'start' => $firstLogDate ?: $today->toDateString(),
            'end' => $today->toDateString(),
        ];
    }

    /**
     * 构建标签排行榜
     */
    private function buildTagRanking(array $logIds, int $limit = 10): array
    {
        if (empty($logIds)) {
            return [];
        }

        return DB::table('work_daily_log_tag')
            ->join('work_daily_tags', 'work_daily_tags.id', '=', 'work_daily_log_tag.work_daily_tag_id')
            ->whereIn('work_daily_log_tag.work_daily_log_id', $logIds)
            ->where('work_daily_tags.deleted_at', 0)
            ->groupBy('work_daily_tags.name')
            ->select('work_daily_tags.name', DB::raw('COUNT(*) as count'))
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => $row->count])
            ->all();
    }

    private function isManager($user): bool
    {
        foreach ($user->roles as $role) {
            if ($role->code === 'super') {
                return true;
            }
        }

        return false;
    }
}
