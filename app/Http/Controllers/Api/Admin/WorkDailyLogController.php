<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkPlatform;
use App\Services\Api\Admin\WorkDailyLogService;
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
        $query = WorkDailyLog::query();
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

        $dailyLogs = $query->orderBy('log_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(self::PER_PAGE);

        // 不再展示 platform 字段，content 返回为解码后的结构（模型的 accessor 已处理）
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

        // 新格式：{ date: 'YYYY-MM-DD', platforms: [{platform_id, content, platform_name?}, ...] }
        $this->validateDailyLog($data);

        $user = auth('api')->user();
        $date = $data['log_date'] ?? ($data['date'] ?? null);
        if (!$date) {
            throw new \Exception('请选择日期');
        }

        // 覆盖策略：如果当天已有记录（按 create_user & date），则替换
        $existing = WorkDailyLog::where('log_date', $date)->where('create_user', $user->id)->first();
        $payload = ['platforms' => $data['platforms'] ?? []];

        if ($existing) {
            $existing->content = $payload;
            $existing->updated_at = time();
            $existing->edit();
            $existing->load('platform');
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

        // 编辑同样接受 platforms 数组
        $this->validateDailyLog($data, false);

        if (isset($data['platforms'])) {
            $workDailyLog->content = ['platforms' => $data['platforms']];
        }
        if (isset($data['log_date'])) {
            $workDailyLog->log_date = $data['log_date'];
        }

        $workDailyLog->updated_at = time();
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

        $user = auth('api')->user();
        $service = new WorkDailyLogService();
        $created = $service->importEntries($user->id, $entries);

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
        $model = $this->resolveReportModel($request);

        $title = "牛马日常月报 - {$month}";
        $markdown = $this->buildSummaryMarkdown($title, $logs, 'month', $model);

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
        $model = $this->resolveReportModel($request);

        $title = "牛马日常周报 - {$start} ~ {$end}";
        $markdown = $this->buildSummaryMarkdown($title, $logs, 'week', $model);

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
        $model = $this->resolveReportModel($request);

        $title = "牛马日常年报 - {$year}";
        $markdown = $this->buildSummaryMarkdown($title, $logs, 'year', $model);

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    /**
     * 获取 OpenClaw 可用模型列表
     *
     * @return \App\Http\Resources\BaseResource
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
     * 校验数据
     *
     * @param array $data
     * @param bool $requireAll
     * @return void
     */
    private function validateDailyLog(array $data, bool $requireAll = true)
    {
        // 新格式校验
        if ($requireAll) {
            $date = $data['log_date'] ?? ($data['date'] ?? null);
            if (!$date) {
                throw new \Exception('请选择日期');
            }
            if (empty($data['platforms']) || !is_array($data['platforms'])) {
                throw new \Exception('请选择至少一个平台并填写内容');
            }
        }

        if (isset($data['platforms']) && is_array($data['platforms'])) {
            foreach ($data['platforms'] as $p) {
                if (empty($p['platform_id']) && empty($p['platform_name'])) {
                    throw new \Exception('平台信息不完整');
                }
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

        $summary = $this->callOpenClaw($prompt, $model);

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
    private function callOpenClaw(string $prompt, ?string $model = null): ?string
    {
        $baseUrl = rtrim(env('OPENCLAW_GATEWAY_URL', 'http://127.0.0.1:18789'), '/');
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
     * @param FormRequest $request
     * @return string|null
     */
    private function resolveReportModel(FormRequest $request): ?string
    {
        $model = trim((string)$request->get('model', ''));
        return $model !== '' ? $model : null;
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
            if (!in_array($defaultModel, $configuredModels, true)) {
                array_unshift($configuredModels, $defaultModel);
            }
            return array_values(array_unique(array_filter($configuredModels)));
        }

        $baseUrl = rtrim(env('OPENCLAW_GATEWAY_URL', 'http://127.0.0.1:18789'), '/');
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

        return $models;
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
