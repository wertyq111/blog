<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\PomoStatsRequest;
use App\Models\Admin\PomoSession;
use App\Models\Admin\PomoTask;
use Carbon\Carbon;

class PomoStatsController extends Controller
{
    /**
     * 记录一次完成的专注段，并给关联任务番茄数 +1。
     *
     * @param PomoStatsRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function storeSession(PomoStatsRequest $request)
    {
        $userId = auth('api')->id();
        $taskId = (int) ($request->input('task_id') ?? 0);

        $session = new PomoSession();
        $session->fill([
            'task_id' => $taskId,
            'day' => Carbon::now()->format('Y-m-d'),
            'completed_at' => time(),
        ]);
        $session->edit();

        if ($taskId > 0) {
            $task = PomoTask::query()
                ->where('create_user', $userId)
                ->whereKey($taskId)
                ->first();
            if ($task) {
                $task->completed_pomos = $task->completed_pomos + 1;
                $task->edit();
            }
        }

        return response()->json([]);
    }

    /**
     * 近 7 天（含今天）每日完成番茄数，缺失日补 0，本地时区口径。
     *
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/8
     */
    public function week()
    {
        $userId = auth('api')->id();

        $start = Carbon::now()->subDays(6)->format('Y-m-d');

        $counts = PomoSession::query()
            ->where('create_user', $userId)
            ->where('day', '>=', $start)
            ->selectRaw('day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');

        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->format('Y-m-d');
            $out[] = ['day' => $day, 'count' => (int) ($counts[$day] ?? 0)];
        }

        return response()->json(['data' => $out]);
    }
}
