<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\WorkDailyLogRequest;
use App\Jobs\GenerateWorkDailyReportExport;
use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkDailyReportExport;
use App\Models\Admin\WorkPlatform;
use App\Services\Api\Admin\WorkDailyLogService;
use App\Services\Api\Admin\WorkDailyReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\QueryBuilder;

class WorkDailyLogController extends Controller
{
    /**
     * 初始化工作日常服务。
     *
     * @param WorkDailyLogService $workDailyLogService
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function __construct(
        private readonly WorkDailyLogService $workDailyLogService,
        private readonly WorkDailyReportService $workDailyReportService
    )
    {
        parent::__construct();
    }

    /**
     * 牛马日常列表 - 分页
     *
     * @param WorkDailyLogRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function index(WorkDailyLogRequest $request, WorkDailyLog $workDailyLog)
    {
        $requestFilters = $workDailyLog->getRequestFilters();
        unset($requestFilters['platform_id']);

        $filters = $request->input('filter', []);
        if (!is_array($filters)) {
            $filters = [];
        }
        unset($filters['platform_id']);

        $queryParameters = $request->query->all();
        if (empty($filters)) {
            unset($queryParameters['filter']);
        } else {
            $queryParameters['filter'] = $filters;
        }

        $allowedFilters = $request->generateAllowedFilters($requestFilters);

        $query = QueryBuilder::for($workDailyLog->newQuery(), $request->duplicate($queryParameters, $request->request->all()))
            ->allowedFilters($allowedFilters);

        foreach ($this->getAuthorizeConditions() as $condition) {
            $query->where($condition[0], $condition[1], $condition[2]);
        }

        $platformId = (int)$request->get('platform_id', 0);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        if ($startDate && $endDate) {
            $query->where('log_date', '>=', $startDate)
                ->where('log_date', '<=', $endDate);
        }

        if ($platformId > 0) {
            $this->applyPlatformFilter($query, $platformId);
        }

        $content = trim((string)$request->get('content', ''));
        if ($content !== '') {
            $query->where('content', 'like', '%' . $content . '%');
        }

        $tagId = (int)$request->get('tag_id', 0);
        if ($tagId > 0) {
            $query->whereHas('tags', fn($q) => $q->where('work_daily_tags.id', $tagId));
        }

        $dailyLogs = $query->with('tags:id,name')
            ->orderBy('log_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($request->perPage());

        $platformNameMap = $this->buildPlatformNameMap($dailyLogs->getCollection());

        $dailyLogs->getCollection()->transform(function (WorkDailyLog $log) use ($platformNameMap) {
            $log->tags_name = $log->tags->pluck('name')->filter()->values()->all();

            $platforms = $this->normalizePlatforms($log, $platformNameMap);
            $log->platforms_name = array_values(array_unique(array_filter(array_map(static function (array $platform) {
                return $platform['platform_name'] ?? null;
            }, $platforms))));

            return $log;
        });

        return $this->resource($dailyLogs, ['time' => true, 'collection' => true]);
    }

    /**
     * 牛马日常详情
     *
     * @param WorkDailyLog $workDailyLog
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function info(WorkDailyLog $workDailyLog)
    {
        $this->authorizeOwner($workDailyLog);

        $workDailyLog->load(['platform', 'tags:id,name']);

        return $this->resource($workDailyLog, ['time' => true]);
    }

    /**
     * 添加牛马日常
     *
     * @param WorkDailyLogRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function add(WorkDailyLogRequest $request, WorkDailyLog $workDailyLog)
    {
        $data = $request->getSnakeRequest();

        $user = auth('api')->user();
        $date = $data['log_date'];

        // 覆盖策略：如果当天已有记录（按 create_user & date），则替换
        $existing = WorkDailyLog::where('log_date', $date)->where('create_user', $user->id)->first();
        $payload = ['platforms' => $data['platforms'] ?? []];

        $tagIds = array_filter(array_map('intval', (array) $request->input('tag_ids', [])));

        if ($existing) {
            $existing->content = $payload;
            $existing->updated_at = time();
            $existing->edit();
            $existing->tags()->sync($tagIds);
            $existing->load(['platform', 'tags:id,name']);
            return $this->resource($existing);
        }

        $workDailyLog->fill([
            'platform_id' => 0,
            'log_date' => $date,
            'content' => $payload,
            'create_user' => $user->id,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $workDailyLog->edit();
        $workDailyLog->tags()->sync($tagIds);

        $workDailyLog->load(['platform', 'tags:id,name']);

        return $this->resource($workDailyLog);
    }

    /**
     * 编辑牛马日常
     *
     * @param WorkDailyLog $workDailyLog
     * @param WorkDailyLogRequest $request
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function edit(WorkDailyLog $workDailyLog, WorkDailyLogRequest $request)
    {
        $this->authorizeOwner($workDailyLog);

        $data = $request->getSnakeRequest();

        if (isset($data['platforms'])) {
            $workDailyLog->content = ['platforms' => $data['platforms']];
        }
        if (isset($data['log_date'])) {
            $workDailyLog->log_date = $data['log_date'];
        }

        $workDailyLog->updated_at = time();
        $workDailyLog->edit();

        if ($request->has('tag_ids')) {
            $tagIds = array_filter(array_map('intval', (array) $request->input('tag_ids', [])));
            $workDailyLog->tags()->sync($tagIds);
        }

        $workDailyLog->load(['platform', 'tags:id,name']);

        return $this->resource($workDailyLog);
    }

    /**
     * 删除牛马日常
     *
     * @param WorkDailyLog $workDailyLog
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
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
     * @param WorkDailyLogRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function import(WorkDailyLogRequest $request)
    {
        $file = $request->file('file');
        $year = $request->get('year') ?: date('Y');

        $content = $file->get();
        $entries = $this->parseMarkdown($content, (int)$year);

        if (empty($entries)) {
            throw new \Exception('未解析到有效数据');
        }

        $user = auth('api')->user();
        $created = $this->workDailyLogService->importEntries($user->id, $entries);

        return response()->json(['count' => $created]);
    }

    /**
     * 月报导出（Markdown）
     *
     * @param WorkDailyLogRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \Illuminate\Http\Response
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function reportMonth(WorkDailyLogRequest $request, WorkDailyLog $workDailyLog)
    {
        $month = $request->get('month');
        $model = $this->resolveReportModel($request);

        $markdown = $this->workDailyReportService->generateForUser((int)auth('api')->id(), 'month', [
            'month' => $month,
        ], $model);

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    /**
     * 周报导出（Markdown）
     *
     * @param WorkDailyLogRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \Illuminate\Http\Response
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function reportWeek(WorkDailyLogRequest $request, WorkDailyLog $workDailyLog)
    {
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        $model = $this->resolveReportModel($request);

        $markdown = $this->workDailyReportService->generateForUser((int)auth('api')->id(), 'week', [
            'start_date' => $start,
            'end_date' => $end,
        ], $model);

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    /**
     * 年报导出（Markdown）
     *
     * @param WorkDailyLogRequest $request
     * @param WorkDailyLog $workDailyLog
     * @return \Illuminate\Http\Response
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function reportYear(WorkDailyLogRequest $request, WorkDailyLog $workDailyLog)
    {
        $year = $request->get('year');
        $model = $this->resolveReportModel($request);

        $markdown = $this->workDailyReportService->generateForUser((int)auth('api')->id(), 'year', [
            'year' => $year,
        ], $model);

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    /**
     * 创建异步报表导出任务。
     *
     * @param WorkDailyLogRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportExport(WorkDailyLogRequest $request)
    {
        $userId = (int)auth('api')->id();
        $type = (string)$request->get('type');
        $payload = $this->resolveReportExportPayload($request, $type);
        $model = $this->resolveReportModel($request);

        $result = Cache::store('redis')
            ->lock('work-daily-report-export:' . $userId, 10)
            ->block(5, function () use ($userId, $type, $payload, $model) {
                $activeExport = WorkDailyReportExport::query()
                    ->where('user_id', $userId)
                    ->whereIn('status', WorkDailyReportExport::activeStatuses())
                    ->orderByDesc('id')
                    ->first();

                if ($activeExport) {
                    return [
                        'blocked' => true,
                        'export' => $this->workDailyReportService->exportData($activeExport),
                    ];
                }

                $export = $this->workDailyReportService->createExport($userId, $type, $payload, $model);
                GenerateWorkDailyReportExport::dispatch($export->id);

                return [
                    'blocked' => false,
                    'export' => $this->workDailyReportService->exportData($export),
                ];
            });

        return response()->json($result);
    }

    /**
     * 获取当前用户最近的报表导出任务。
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function currentReportExport()
    {
        $export = WorkDailyReportExport::query()
            ->where('user_id', (int)auth('api')->id())
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'export' => $export ? $this->workDailyReportService->exportData($export) : null,
            'active' => $export ? in_array($export->status, WorkDailyReportExport::activeStatuses(), true) : false,
        ]);
    }

    /**
     * 获取当前用户的报表导出任务分页列表。
     *
     * @param WorkDailyLogRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listReportExports(WorkDailyLogRequest $request)
    {
        $userId = (int)auth('api')->id();
        $pageSize = max(1, min(50, (int)$request->get('page_size', 20)));

        $paginator = WorkDailyReportExport::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($pageSize);

        $items = collect($paginator->items())
            ->map(fn(WorkDailyReportExport $export) => $this->workDailyReportService->exportData($export))
            ->all();

        return response()->json([
            'items' => $items,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
        ]);
    }

    /**
     * 获取报表导出任务详情。
     *
     * @param WorkDailyReportExport $export
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportExportInfo(WorkDailyReportExport $export)
    {
        $this->authorizeReportExport($export);

        return response()->json([
            'export' => $this->workDailyReportService->exportData($export),
            'active' => in_array($export->status, WorkDailyReportExport::activeStatuses(), true),
        ]);
    }

    /**
     * 下载已完成的报表文件，format=md 为 Markdown 原文，format=html 为自带阅读样式的 HTML。
     *
     * @param Request $request
     * @param WorkDailyReportExport $export
     * @return \Illuminate\Http\Response
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/12
     */
    public function downloadReportExport(Request $request, WorkDailyReportExport $export)
    {
        $this->authorizeReportExport($export);
        if (!$export->isCompleted()) {
            abort(409, '导出任务尚未完成');
        }

        $format = (string)$request->query('format', 'md');
        if (!in_array($format, ['md', 'html'], true)) {
            abort(422, '不支持的下载格式');
        }

        $fileName = $export->file_name ?: '工作报表.md';

        if ($format === 'html') {
            $htmlName = preg_replace('/\.md$/u', '', $fileName) . '.html';

            return response($this->workDailyReportService->renderHtml($export), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($htmlName),
            ]);
        }

        return response((string)$export->content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($fileName),
        ]);
    }

    /**
     * 编辑保存已完成报表的 Markdown 内容。
     *
     * @param WorkDailyLogRequest $request
     * @param WorkDailyReportExport $export
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/12
     */
    public function updateReportExportContent(WorkDailyLogRequest $request, WorkDailyReportExport $export)
    {
        $this->authorizeReportExport($export);
        if (!$export->isCompleted()) {
            abort(409, '仅可编辑已完成的报表');
        }

        $export = $this->workDailyReportService->updateExportContent($export, (string)$request->get('content'));

        return response()->json([
            'export' => $this->workDailyReportService->exportData($export),
        ]);
    }

    /**
     * 删除报表导出记录。
     *
     * @param WorkDailyReportExport $export
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/12
     */
    public function deleteReportExport(WorkDailyReportExport $export)
    {
        $this->authorizeReportExport($export);

        $export->delete();

        return response()->json([]);
    }

    /**
     * 获取 OpenClaw 可用模型列表
     *
     * @return \App\Http\Resources\BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    public function reportModels()
    {
        $defaultModel = env('OPENCLAW_MODEL', 'github-copilot/gpt-5.2-codex');
        $models = $this->fetchOpenClawModels($defaultModel);

        return $this->resource([
            'models' => $models,
            'current_model' => $defaultModel,
        ]);
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

        $platformNameMap = $this->buildPlatformNameMap($logs);
        $grouped = $logs->groupBy('log_date');

        foreach ($grouped as $date => $items) {
            $markdown .= "## {$date}\n\n";
            foreach ($items as $item) {
                $platforms = $this->normalizePlatforms($item, $platformNameMap);
                if (empty($platforms)) {
                    $platformName = $item->platform ? $item->platform->name : '未指定平台';
                    $content = is_array($item->content)
                        ? json_encode($item->content, JSON_UNESCAPED_UNICODE)
                        : $item->content;
                    $markdown .= "### 平台：{$platformName}\n\n";
                    $markdown .= trim((string)$content) . "\n\n";
                    continue;
                }

                foreach ($platforms as $platform) {
                    $platformName = $platform['platform_name'] ?? ($platformNameMap[$platform['platform_id']] ?? '未指定平台');
                    $content = $platform['content'] ?? '';
                    $markdown .= "### 平台：{$platformName}\n\n";
                    $markdown .= trim((string)$content) . "\n\n";
                }
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
    private function buildSummaryMarkdown(string $title, $logs, string $type = 'month', ?string $model = null): string
    {
        if ($logs->isEmpty()) {
            return "# {$title}\n\n暂无记录。\n";
        }

        $platformNameMap = $this->buildPlatformNameMap($logs);
        $platformGroups = [];
        foreach ($logs as $item) {
            $platforms = $this->normalizePlatforms($item, $platformNameMap);
            if (empty($platforms)) {
                $platformName = $item->platform ? $item->platform->name : '未指定平台';
                $content = is_array($item->content)
                    ? json_encode($item->content, JSON_UNESCAPED_UNICODE)
                    : $item->content;
                $platformGroups[$platformName][] = [
                    'date' => $item->log_date,
                    'content' => $content
                ];
                continue;
            }

            foreach ($platforms as $platform) {
                $platformName = $platform['platform_name'] ?? ($platformNameMap[$platform['platform_id']] ?? '未指定平台');
                $platformGroups[$platformName][] = [
                    'date' => $item->log_date,
                    'content' => $platform['content'] ?? ''
                ];
            }
        }

        $source = "";
        foreach ($platformGroups as $platform => $items) {
            $source .= "## {$platform}\n";
            foreach ($items as $item) {
                $source .= "- {$item['date']}: " . str_replace("\n", " ", trim((string)$item['content'])) . "\n";
            }
            $source .= "\n";
        }

        $styleHint = match ($type) {
            'month' => '月报风格：强调本月对各平台做了哪些操作/修改、完善了哪些模块，突出产出与改进。',
            'week' => '周报风格：简洁列出本周重点事项、进展、问题与解决，按平台归纳。',
            'year' => '年报风格：年终总结语气，按平台归纳年度成果、关键项目、优化与经验沉淀。',
            default => '按平台归纳总结，突出产出。'
        };

        $prompt = "你是工作日志总结助手。请基于以下原始记录，按平台归纳输出中文 Markdown 总结：\n" .
            "- 顶部保留标题 {$title}\n" .
            "- 每个平台一个二级标题\n" .
            "- 每个平台用 3-6 条要点总结，不要逐条复述\n" .
            "- {$styleHint}\n" .
            "- 保持简洁、可汇报\n\n" .
            "原始记录：\n{$source}";

        $summary = $this->callReportModel($prompt, $model);

        if (!$summary) {
            return $this->buildMarkdown($title, $logs);
        }

        return "# {$title}\n\n" . trim($summary) . "\n";
    }

    /**
     * 调用报表模型生成总结
     *
     * @param string $prompt
     * @param string|null $model
     * @return string|null
     */
    private function callReportModel(string $prompt, ?string $model = null): ?string
    {
        $targetModel = $model ?: env('OPENCLAW_MODEL', 'github-copilot/gpt-5.2-codex');

        if ($this->isLocalCodexModel($targetModel)) {
            return $this->callLocalCodex($prompt, $targetModel);
        }

        if ($this->isLocalAgyModel($targetModel)) {
            return $this->callLocalAgy($prompt, $targetModel);
        }

        if ($this->isLocalClaudeModel($targetModel)) {
            return $this->callLocalClaude($prompt, $targetModel);
        }

        return $this->callOpenClaw($prompt, $targetModel);
    }

    /**
     * 调用 OpenClaw Gateway 生成总结
     *
     * @param string $prompt
     * @param string|null $model
     * @return string|null
     */
    private function callOpenClaw(string $prompt, ?string $model = null): ?string
    {
        $baseUrl = $this->resolveOpenClawGatewayUrl();
        if (!$baseUrl) {
            Log::warning('OpenClaw summary skipped: missing OPENCLAW_GATEWAY_URL');
            return null;
        }

        $targetModel = $model ?: env('OPENCLAW_MODEL', 'github-copilot/gpt-5.2-codex');
        $token = env('OPENCLAW_GATEWAY_TOKEN');

        if (str_starts_with($targetModel, 'bailian/')) {
            return $this->callBailianDirect($prompt, $targetModel);
        }

        try {
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            $response = Http::withHeaders($headers)->post($baseUrl . '/v1/chat/completions', [
                'model' => $targetModel,
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
            $content = $data['choices'][0]['message']['content'] ?? null;
            if (is_string($content) && str_starts_with($content, 'LLM request rejected:')) {
                Log::warning('OpenClaw summary rejected', [
                    'model' => $targetModel,
                    'content' => $content,
                ]);
                return null;
            }

            return $content;
        } catch (\Exception $e) {
            Log::error('OpenClaw summary exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 调用本机 Codex CLI bridge 生成总结
     *
     * @param string $prompt
     * @param string $model
     * @return string
     */
    private function callLocalCodex(string $prompt, string $model): string
    {
        $baseUrl = $this->resolveLocalCodexBridgeUrl();
        if (!$baseUrl) {
            throw new \RuntimeException('LOCAL_CODEX_BRIDGE_URL 未配置');
        }

        $headers = [];
        $token = config('services.local_codex.bridge_token');
        if (is_string($token) && trim($token) !== '') {
            $headers['Authorization'] = 'Bearer ' . trim($token);
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(240)
                ->post($baseUrl . '/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => '你是一个擅长按平台归纳工作日志的助手，输出中文 Markdown。'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException($this->bridgeUnreachableMessage('Codex'), 0, $e);
        }

        if (!$response->ok()) {
            throw new \RuntimeException('Local Codex summary failed: ' . $response->status() . ' ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Local Codex summary returned empty content');
        }

        return $content;
    }

    /**
     * 调用本机 Agy CLI bridge 生成总结
     *
     * @param string $prompt
     * @param string $model
     * @return string
     */
    private function callLocalAgy(string $prompt, string $model): string
    {
        $baseUrl = $this->resolveLocalAgyBridgeUrl();
        if (!$baseUrl) {
            throw new \RuntimeException('LOCAL_AGY_BRIDGE_URL 未配置');
        }

        $headers = [];
        $token = config('services.local_agy.bridge_token');
        if (is_string($token) && trim($token) !== '') {
            $headers['Authorization'] = 'Bearer ' . trim($token);
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(240)
                ->post($baseUrl . '/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => '你是一个擅长按平台归纳工作日志的助手，输出中文 Markdown。'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException($this->bridgeUnreachableMessage('Agy'), 0, $e);
        }

        if (!$response->ok()) {
            throw new \RuntimeException('Local Agy summary failed: ' . $response->status() . ' ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Local Agy summary returned empty content');
        }

        return $content;
    }

    private function callLocalClaude(string $prompt, string $model): string
    {
        $baseUrl = $this->resolveLocalClaudeBridgeUrl();
        if (!$baseUrl) {
            throw new \RuntimeException('LOCAL_CLAUDE_BRIDGE_URL 未配置');
        }

        $headers = [];
        $token = config('services.local_claude.bridge_token');
        if (is_string($token) && trim($token) !== '') {
            $headers['Authorization'] = 'Bearer ' . trim($token);
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(240)
                ->post($baseUrl . '/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => '你是一个擅长按平台归纳工作日志的助手，输出中文 Markdown。'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException($this->bridgeUnreachableMessage('Claude'), 0, $e);
        }

        if (!$response->ok()) {
            throw new \RuntimeException('Local Claude summary failed: ' . $response->status() . ' ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Local Claude summary returned empty content');
        }

        return $content;
    }

    /**
     * Bailian 模型直连 DashScope，绕开 OpenClaw 网关的认证问题
     *
     * @param string $prompt
     * @param string $model
     * @return string|null
     */
    private function callBailianDirect(string $prompt, string $model): ?string
    {
        $apiKey = env('OPENCLAW_BAILIAN_API_KEY');
        if (!$apiKey) {
            Log::warning('Bailian direct summary skipped: missing OPENCLAW_BAILIAN_API_KEY');
            return null;
        }

        $modelId = preg_replace('/^bailian\//', '', $model);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post('https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions', [
                'model' => $modelId,
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => '你是一个擅长按平台归纳工作日志的助手，输出中文 Markdown。'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (!$response->ok()) {
                Log::warning('Bailian direct summary failed', [
                    'model' => $modelId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error('Bailian direct summary exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 构建平台 ID => 名称映射
     *
     * @param \Illuminate\Support\Collection $logs
     * @return array
     */
    private function buildPlatformNameMap($logs): array
    {
        $ids = [];
        foreach ($logs as $item) {
            if (!empty($item->platform_id)) {
                $ids[] = $item->platform_id;
            }
            if (is_array($item->content) && isset($item->content['platforms'])) {
                foreach ($item->content['platforms'] as $platform) {
                    if (!empty($platform['platform_id'])) {
                        $ids[] = $platform['platform_id'];
                    }
                }
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if (empty($ids)) {
            return [];
        }

        return WorkPlatform::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * 标准化 content 中的平台结构
     *
     * @param WorkDailyLog $item
     * @param array $platformNameMap
     * @return array
     */
    private function normalizePlatforms(WorkDailyLog $item, array $platformNameMap): array
    {
        if (!is_array($item->content) || !isset($item->content['platforms'])) {
            return [];
        }

        $platforms = [];
        foreach ($item->content['platforms'] as $platform) {
            if (!is_array($platform)) {
                continue;
            }
            $platformId = $platform['platform_id'] ?? $item->platform_id ?? 0;
            $platformName = $platform['platform_name'] ?? ($platformId ? ($platformNameMap[$platformId] ?? null) : null);
            $platforms[] = [
                'platform_id' => $platformId,
                'platform_name' => $platformName,
                'content' => $platform['content'] ?? ''
            ];
        }

        return $platforms;
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
     * 平台筛选兼容新旧两种数据结构
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $platformId
     * @return void
     */
    private function applyPlatformFilter($query, int $platformId): void
    {
        $pattern = '"platform_id":[[:space:]]*' . $platformId . '([^0-9]|$)';

        $query->where(function ($builder) use ($platformId, $pattern) {
            $builder->where('platform_id', $platformId)
                ->orWhereRaw('content REGEXP ?', [$pattern]);
        });
    }

    /**
     * 从请求中读取报表模型
     *
     * @param WorkDailyLogRequest $request
     * @return string|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/5/26
     */
    private function resolveReportModel(WorkDailyLogRequest $request): ?string
    {
        $model = trim((string)$request->get('model', ''));
        return $model !== '' ? $model : null;
    }

    /**
     * 从请求中读取异步报表任务参数。
     *
     * @param WorkDailyLogRequest $request
     * @param string $type
     * @return array
     */
    private function resolveReportExportPayload(WorkDailyLogRequest $request, string $type): array
    {
        return match ($type) {
            'month' => ['month' => $request->get('month')],
            'week' => [
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date'),
            ],
            'year' => ['year' => $request->get('year')],
            default => throw new \InvalidArgumentException('报表类型不正确'),
        };
    }

    /**
     * 限制用户只能查看和下载自己的导出记录。
     *
     * @param WorkDailyReportExport $export
     * @return void
     */
    private function authorizeReportExport(WorkDailyReportExport $export): void
    {
        if ((int)$export->user_id !== (int)auth('api')->id()) {
            abort(403, '无权访问该导出记录');
        }
    }

    /**
     * 拉取 OpenClaw 模型列表
     *
     * @param string $defaultModel
     * @return array
     */
    private function fetchOpenClawModels(string $defaultModel): array
    {
        $configuredModels = $this->parseConfiguredReportModels();
        if (!empty($configuredModels)) {
            $configuredModels = $this->withLocalCodexModel($configuredModels);
            $configuredModels = $this->withLocalAgyModel($configuredModels);
            $configuredModels = $this->withLocalClaudeModel($configuredModels);
            if (!in_array($defaultModel, $configuredModels, true)) {
                array_unshift($configuredModels, $defaultModel);
            }
            return array_values(array_unique(array_filter($configuredModels)));
        }

        $baseUrl = $this->resolveOpenClawGatewayUrl();
        if (!$baseUrl) {
            return $this->withLocalClaudeModel($this->withLocalAgyModel($this->withLocalCodexModel([$defaultModel])));
        }

        $token = env('OPENCLAW_GATEWAY_TOKEN');

        $models = [];

        try {
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($baseUrl . '/v1/models');

            if ($response->ok()) {
                $data = $response->json('data', []);
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if (is_array($item) && !empty($item['id'])) {
                            $models[] = (string)$item['id'];
                        }
                    }
                }
            } else {
                Log::warning('OpenClaw model list failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('OpenClaw model list exception: ' . $e->getMessage());
        }

        $models = array_values(array_unique(array_filter($models)));
        if (empty($models)) {
            return [$defaultModel];
        }
        if (!in_array($defaultModel, $models, true)) {
            array_unshift($models, $defaultModel);
        }

        return $this->withLocalClaudeModel($this->withLocalAgyModel($this->withLocalCodexModel($models)));
    }

    private function resolveOpenClawGatewayUrl(): ?string
    {
        $baseUrl = config('services.openclaw.gateway_url');

        return is_string($baseUrl) && trim($baseUrl) !== ''
            ? rtrim($baseUrl, '/')
            : null;
    }

    private function resolveLocalCodexBridgeUrl(): ?string
    {
        $baseUrl = config('services.local_codex.bridge_url');

        return is_string($baseUrl) && trim($baseUrl) !== ''
            ? rtrim($baseUrl, '/')
            : null;
    }

    private function resolveLocalClaudeBridgeUrl(): ?string
    {
        $baseUrl = config('services.local_claude.bridge_url');

        return is_string($baseUrl) && trim($baseUrl) !== ''
            ? rtrim($baseUrl, '/')
            : null;
    }

    private function resolveLocalAgyBridgeUrl(): ?string
    {
        $baseUrl = config('services.local_agy.bridge_url');

        return is_string($baseUrl) && trim($baseUrl) !== ''
            ? rtrim($baseUrl, '/')
            : null;
    }

    private function isLocalCodexModel(string $model): bool
    {
        return str_starts_with($model, 'local-codex/');
    }

    private function isLocalAgyModel(string $model): bool
    {
        return str_starts_with($model, 'local-agy/');
    }

    private function isLocalClaudeModel(string $model): bool
    {
        return str_starts_with($model, 'local-claude/');
    }

    private function bridgeUnreachableMessage(string $name): string
    {
        return sprintf(
            '本机 %s CLI 桥未连通。请在本机执行 `scripts/local-bridges.sh up`，并在远端执行 `scripts/remote-bridges-socat.sh up`，确保桥服务/SSH 隧道/socat 转发全部就位后重试。',
            $name
        );
    }

    private function withLocalCodexModel(array $models): array
    {
        $localModel = config('services.local_codex.model', 'local-codex/codex-cli');
        if (!is_string($localModel) || trim($localModel) === '') {
            $localModel = 'local-codex/codex-cli';
        }
        $models[] = trim($localModel);

        return array_values(array_unique(array_filter($models)));
    }

    private function withLocalAgyModel(array $models): array
    {
        $localModel = config('services.local_agy.model', 'local-agy/gemini-3.5-flash-high');
        if (!is_string($localModel) || trim($localModel) === '') {
            $localModel = 'local-agy/gemini-3.5-flash-high';
        }
        $models[] = trim($localModel);

        return array_values(array_unique(array_filter($models)));
    }

    private function withLocalClaudeModel(array $models): array
    {
        $localModel = config('services.local_claude.model', 'local-claude/claude-cli');
        if (!is_string($localModel) || trim($localModel) === '') {
            $localModel = 'local-claude/claude-cli';
        }
        $models[] = trim($localModel);

        return array_values(array_unique(array_filter($models)));
    }

    /**
     * 读取环境变量里配置的报表模型列表
     *
     * @return array
     */
    private function parseConfiguredReportModels(): array
    {
        $raw = trim((string)env('OPENCLAW_REPORT_MODELS', ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map(function ($item) {
                return is_string($item) ? trim($item) : '';
            }, $decoded)));
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
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
