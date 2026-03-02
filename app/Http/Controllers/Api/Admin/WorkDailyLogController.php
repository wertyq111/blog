<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkPlatform;
use Carbon\Carbon;

class WorkDailyLogController extends Controller
{
    /**
     * 牛马日常列表 - 分页
     *
     * @param FormRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \App\Http\Resources\BaseResource
     */
    public function index(FormRequest $request, WorkDailyLog $workDailyLog)
    {
        $allowedFilters = $request->generateAllowedFilters($workDailyLog->getRequestFilters());

        $conditions = $this->getAuthorizeConditions();

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        if ($startDate && $endDate) {
            $conditions[] = ['log_date', '>=', $startDate];
            $conditions[] = ['log_date', '<=', $endDate];
        }

        $config = [
            'allowedFilters' => $allowedFilters,
            'conditions' => $conditions,
            'orderBy' => [['log_date' => 'desc'], ['id' => 'desc']]
        ];

        $dailyLogs = $this->queryBuilder($workDailyLog, true, $config);

        $dailyLogs->load('platform');

        return $this->resource($dailyLogs, ['time' => true, 'collection' => true]);
    }

    /**
     * 牛马日常详情
     *
     * @param WorkDailyLog $workDailyLog
     * @return \App\Http\Resources\BaseResource
     */
    public function info(WorkDailyLog $workDailyLog)
    {
        $this->authorizeOwner($workDailyLog);

        $workDailyLog->load('platform');

        return $this->resource($workDailyLog, ['time' => true]);
    }

    /**
     * 添加牛马日常
     *
     * @param FormRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \App\Http\Resources\BaseResource
     */
    public function add(FormRequest $request, WorkDailyLog $workDailyLog)
    {
        $data = $request->getSnakeRequest();

        $this->validateDailyLog($data);

        $workDailyLog->fill($data);
        $workDailyLog->edit();

        $workDailyLog->load('platform');

        return $this->resource($workDailyLog);
    }

    /**
     * 编辑牛马日常
     *
     * @param WorkDailyLog $workDailyLog
     * @param FormRequest $request
     * @return \App\Http\Resources\BaseResource
     */
    public function edit(WorkDailyLog $workDailyLog, FormRequest $request)
    {
        $this->authorizeOwner($workDailyLog);

        $data = $request->getSnakeRequest();

        $this->validateDailyLog($data, false);

        $workDailyLog->fill($data);
        $workDailyLog->edit();

        $workDailyLog->load('platform');

        return $this->resource($workDailyLog);
    }

    /**
     * 删除牛马日常
     *
     * @param WorkDailyLog $workDailyLog
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(WorkDailyLog $workDailyLog)
    {
        $this->authorizeOwner($workDailyLog);

        $workDailyLog->delete();

        return response()->json([]);
    }

    /**
     * 月报导出（Markdown）
     *
     * @param FormRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \Illuminate\Http\Response
     */
    public function reportMonth(FormRequest $request, WorkDailyLog $workDailyLog)
    {
        $month = $request->get('month');
        if (!$month) {
            throw new \Exception('请选择月份');
        }

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        $logs = $this->fetchLogs($workDailyLog, $start, $end);

        $title = "牛马日常月报 - {$month}";
        $markdown = $this->buildMarkdown($title, $logs);

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    /**
     * 周报导出（Markdown）
     *
     * @param FormRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \Illuminate\Http\Response
     */
    public function reportWeek(FormRequest $request, WorkDailyLog $workDailyLog)
    {
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        if (!$start || !$end) {
            throw new \Exception('请选择日期范围');
        }

        $logs = $this->fetchLogs($workDailyLog, $start, $end);

        $title = "牛马日常周报 - {$start} ~ {$end}";
        $markdown = $this->buildMarkdown($title, $logs);

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    /**
     * 年报导出（Markdown）
     *
     * @param FormRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \Illuminate\Http\Response
     */
    public function reportYear(FormRequest $request, WorkDailyLog $workDailyLog)
    {
        $year = $request->get('year');
        if (!$year) {
            throw new \Exception('请选择年份');
        }

        $start = Carbon::createFromFormat('Y', $year)->startOfYear()->toDateString();
        $end = Carbon::createFromFormat('Y', $year)->endOfYear()->toDateString();

        $logs = $this->fetchLogs($workDailyLog, $start, $end);

        $title = "牛马日常年报 - {$year}";
        $markdown = $this->buildMarkdown($title, $logs);

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    /**
     * 校验数据
     *
     * @param array $data
     * @param bool $requireAll
     * @return void
     */
    private function validateDailyLog(array $data, bool $requireAll = true)
    {
        if ($requireAll && empty($data['platform_id'])) {
            throw new \Exception('请选择平台');
        }

        if ($requireAll && empty($data['log_date'])) {
            throw new \Exception('请选择日期');
        }

        if ($requireAll && empty($data['content'])) {
            throw new \Exception('请填写内容');
        }

        if (isset($data['platform_id'])) {
            $platform = WorkPlatform::query()->where('id', $data['platform_id'])->first();
            if (!$platform) {
                throw new \Exception('平台不存在');
            }
        }
    }

    /**
     * 获取当前用户的查询条件
     *
     * @return array
     */
    private function getAuthorizeConditions(): array
    {
        $user = auth('api')->user();
        $isManager = false;
        foreach ($user->roles as $role) {
            if ($role->code === 'super') {
                $isManager = true;
                break;
            }
        }

        if ($isManager) {
            return [];
        }

        return [['create_user', '=', $user->id]];
    }

    /**
     * 获取日志数据
     *
     * @param WorkDailyLog $workDailyLog
     * @param string $start
     * @param string $end
     * @return \Illuminate\Support\Collection
     */
    private function fetchLogs(WorkDailyLog $workDailyLog, string $start, string $end)
    {
        $conditions = $this->getAuthorizeConditions();
        $conditions[] = ['log_date', '>=', $start];
        $conditions[] = ['log_date', '<=', $end];

        $config = [
            'conditions' => $conditions,
            'orderBy' => [['log_date' => 'asc'], ['id' => 'asc']]
        ];

        $logs = $this->queryBuilder($workDailyLog, false, $config);

        return $logs->load('platform');
    }

    /**
     * 生成Markdown
     *
     * @param string $title
     * @param \Illuminate\Support\Collection $logs
     * @return string
     */
    private function buildMarkdown(string $title, $logs): string
    {
        $markdown = "# {$title}\n\n";

        if ($logs->isEmpty()) {
            return $markdown . "暂无记录。\n";
        }

        $grouped = $logs->groupBy('log_date');

        foreach ($grouped as $date => $items) {
            $markdown .= "## {$date}\n\n";
            foreach ($items as $item) {
                $platformName = $item->platform ? $item->platform->name : '未指定平台';
                $markdown .= "### 平台：{$platformName}\n\n";
                $markdown .= trim($item->content) . "\n\n";
            }
        }

        return $markdown;
    }

    /**
     * 校验数据归属
     *
     * @param WorkDailyLog $workDailyLog
     * @return void
     */
    private function authorizeOwner(WorkDailyLog $workDailyLog): void
    {
        $user = auth('api')->user();
        $isManager = false;
        foreach ($user->roles as $role) {
            if ($role->code === 'super') {
                $isManager = true;
                break;
            }
        }

        if (!$isManager && $workDailyLog->create_user != $user->id) {
            throw new \Exception('无权限操作该记录');
        }
    }
}
