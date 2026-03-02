<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkPlatform;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * 导入Markdown日常
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(FormRequest $request)
    {
        $file = $request->file('file');
        if (!$file) {
            throw new \Exception('请上传Markdown文件');
        }

        $year = $request->get('year');
        if (!$year) {
            $year = date('Y');
        }

        $content = $file->get();
        $entries = $this->parseMarkdown($content, (int)$year);

        if (empty($entries)) {
            throw new \Exception('未解析到有效数据');
        }

        $platformMap = WorkPlatform::query()->get()->keyBy('name');
        $created = 0;
        foreach ($entries as $entry) {
            $platform = $platformMap->get($entry['platform']);
            if (!$platform) {
                $platform = new WorkPlatform();
                $platform->fill([
                    'name' => $entry['platform'],
                    'status' => 1,
                    'sort' => 0
                ]);
                $platform->edit();
                $platformMap->put($entry['platform'], $platform);
            }

            $log = new WorkDailyLog();
            $log->fill([
                'platform_id' => $platform->id,
                'log_date' => $entry['date'],
                'content' => $entry['content']
            ]);
            $log->edit();
            $created++;
        }

        return response()->json(['count' => $created]);
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
        $markdown = $this->buildSummaryMarkdown($title, $logs);

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
        $markdown = $this->buildSummaryMarkdown($title, $logs);

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
        $markdown = $this->buildSummaryMarkdown($title, $logs);

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
     * 生成总结Markdown（调用OpenClaw）
     *
     * @param string $title
     * @param \Illuminate\Support\Collection $logs
     * @return string
     */
    private function buildSummaryMarkdown(string $title, $logs): string
    {
        if ($logs->isEmpty()) {
            return "# {$title}\n\n暂无记录。\n";
        }

        $platformGroups = $logs->groupBy(function ($item) {
            return $item->platform ? $item->platform->name : '未指定平台';
        });

        $source = "";
        foreach ($platformGroups as $platform => $items) {
            $source .= "## {$platform}\n";
            foreach ($items as $item) {
                $source .= "- {$item->log_date}: " . str_replace("\n", " ", trim($item->content)) . "\n";
            }
            $source .= "\n";
        }

        $prompt = "你是工作日志总结助手。请基于以下原始记录，按平台归纳输出 Markdown 总结：\n" .
            "- 顶部保留标题 {$title}\n" .
            "- 每个平台一个二级标题\n" .
            "- 每个平台用 3-6 条要点总结，不要逐条复述\n" .
            "- 保持简洁、可汇报\n\n" .
            "原始记录：\n{$source}";

        $summary = $this->callOpenClaw($prompt);

        if (!$summary) {
            return $this->buildMarkdown($title, $logs);
        }

        return "# {$title}\n\n" . trim($summary) . "\n";
    }

    /**
     * 调用 OpenClaw Gateway 生成总结
     *
     * @param string $prompt
     * @return string|null
     */
    private function callOpenClaw(string $prompt): ?string
    {
        $baseUrl = rtrim(env('OPENCLAW_GATEWAY_URL', 'http://127.0.0.1:18789'), '/');
        $model = env('OPENCLAW_MODEL', 'github-copilot/gpt-5.2-codex');
        $token = env('OPENCLAW_GATEWAY_TOKEN');

        try {
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            $response = Http::withHeaders($headers)->post($baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => '你是一个擅长按平台归纳工作日志的助手，输出中文 Markdown。'],
                    ['role' => 'user', 'content' => $prompt]
                ]
            ]);

            if (!$response->ok()) {
                Log::warning('OpenClaw summary failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error('OpenClaw summary exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 解析Markdown为日志条目
     *
     * @param string $content
     * @param int $year
     * @return array
     */
    private function parseMarkdown(string $content, int $year): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $entries = [];
        $currentDate = null;
        $inTable = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $inTable = false;
                continue;
            }

            if (preg_match('/^###\s*(\d+)\s*月\s*(\d+)\s*日/', $line, $matches)) {
                $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $currentDate = $year . '-' . $month . '-' . $day;
                $inTable = false;
                continue;
            }

            if (strpos($line, '|') === 0) {
                // skip header and alignment rows
                if (str_contains($line, '项目') && str_contains($line, '内容')) {
                    $inTable = true;
                    continue;
                }
                if (preg_match('/^\|\s*:?[-]+/', $line)) {
                    $inTable = true;
                    continue;
                }
                if ($currentDate && $inTable) {
                    $parts = array_values(array_filter(array_map('trim', explode('|', $line)), fn($v) => $v !== ''));
                    if (count($parts) >= 2) {
                        $platform = $parts[0];
                        $contentCell = $parts[1];
                        $contentCell = str_replace(['<br>', '<br/>', '<br />'], "\n", $contentCell);
                        $contentCell = html_entity_decode($contentCell);
                        $entries[] = [
                            'date' => $currentDate,
                            'platform' => $platform,
                            'content' => trim($contentCell)
                        ];
                    }
                }
            }
        }

        return $entries;
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
