<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Services\Api\Admin\Dashboard\DashboardCache;
use App\Services\Api\Admin\Dashboard\HeatmapBuilder;
use App\Services\Api\Admin\Dashboard\PlatformBreakdown;
use App\Services\Api\Admin\Dashboard\StatsAggregator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardCache $dashboardCache,
        private readonly StatsAggregator $statsAggregator,
        private readonly HeatmapBuilder $heatmapBuilder,
        private readonly PlatformBreakdown $platformBreakdown,
    ) {
    }

    public function stats(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
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

        $response = [
            'view' => $view,
            'range' => $range,
            'generated_at' => time(),
        ];

        if ($view === 'overview') {
            $overviewLogs = $this->statsAggregator->fetchLogsBetween($overviewWindow['start'], $overviewWindow['end'], $userId, $isManager);
            $response['metrics'] = $this->statsAggregator->build($overviewLogs, $overviewWindow, $range, $userId, $isManager);
            $response['heatmap'] = $this->heatmapBuilder->build(
                $this->statsAggregator->fetchLogsBetween($heatmapWindow['start'], $heatmapWindow['end'], $userId, $isManager),
                $heatmapWindow
            );
            $response['trend_30d'] = $this->heatmapBuilder->buildTrend(
                $this->statsAggregator->fetchLogsBetween($trendWindow['start'], $trendWindow['end'], $userId, $isManager),
                $trendWindow
            );

            return $response;
        }

        $platformLogs = $this->statsAggregator->fetchLogsBetween($overviewWindow['start'], $overviewWindow['end'], $userId, $isManager);
        $matrixWindow = [
            'start' => Carbon::today('Asia/Shanghai')->subMonths(11)->startOfMonth()->toDateString(),
            'end' => Carbon::today('Asia/Shanghai')->endOfMonth()->toDateString(),
        ];
        $response['rank'] = $this->platformBreakdown->buildRank($platformLogs);
        $response['matrix'] = $this->platformBreakdown->buildMatrix(
            $this->statsAggregator->fetchLogsBetween($matrixWindow['start'], $matrixWindow['end'], $userId, $isManager)
        );

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
