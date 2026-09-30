<?php

namespace App\Services\Api\Admin;

use App\Models\Admin\WorkDailyLog;
use App\Models\Admin\WorkDailyReportExport;
use App\Models\Admin\WorkPlatform;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkDailyReportService
{
    public function __construct(
        private readonly StyledHtmlExportService $styledHtmlExportService
    ) {
    }

    /**
     * 创建一次导出的全部报表任务：工作报表必建，区间内有「个人」大类记录时再建一份个人成长记录。
     *
     * @param int $userId
     * @param string $type
     * @param array $payload
     * @param string|null $model
     * @return WorkDailyReportExport[]
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/30
     */
    public function createExports(int $userId, string $type, array $payload, ?string $model): array
    {
        [$start, $end] = $this->resolveRange($type, $payload);
        $user = User::query()->with('roles')->findOrFail($userId);
        $groups = $this->groupRecordsByCategory($this->fetchLogs($user, $start, $end));

        $exports = [$this->createExport($userId, $type, WorkDailyReportExport::KIND_WORK, $payload, $model)];
        if (!empty($groups[WorkPlatform::CATEGORY_PERSONAL])) {
            $exports[] = $this->createExport($userId, $type, WorkDailyReportExport::KIND_GROWTH, $payload, $model);
        }

        return $exports;
    }

    private function createExport(int $userId, string $type, string $kind, array $payload, ?string $model): WorkDailyReportExport
    {
        [$start, $end] = $this->resolveRange($type, $payload);
        $fileName = $this->buildFileName($type, $kind, $payload);

        $export = new WorkDailyReportExport();
        $export->fill([
            'user_id' => $userId,
            'type' => $type,
            'kind' => $kind,
            'period_start' => $start,
            'period_end' => $end,
            'model' => $model,
            'status' => WorkDailyReportExport::STATUS_PENDING,
            'file_name' => $fileName,
            'started_at' => 0,
            'finished_at' => 0,
        ]);
        $export->create_user = $userId;
        $export->update_user = $userId;
        $export->save();

        return $export;
    }

    public function generate(WorkDailyReportExport $export): string
    {
        $user = User::query()->with('roles')->findOrFail($export->user_id);
        $groups = $this->groupRecordsByCategory($this->fetchLogs($user, $export->period_start, $export->period_end));
        $title = $this->buildTitle($export->type, $export->kind, $export->period_start, $export->period_end);
        $previousOverview = $this->findPreviousOverview($export->user_id, $export->type, $export->kind, $export->period_start);

        if ($export->kind === WorkDailyReportExport::KIND_GROWTH) {
            return $this->buildGrowthMarkdown($title, $groups[WorkPlatform::CATEGORY_PERSONAL], $export->type, $export->model, $previousOverview);
        }

        return $this->buildSummaryMarkdown($title, $groups, $export->type, $export->model, $previousOverview);
    }

    public function generateForUser(int $userId, string $type, array $payload, ?string $model): string
    {
        [$start, $end] = $this->resolveRange($type, $payload);
        $user = User::query()->with('roles')->findOrFail($userId);
        $groups = $this->groupRecordsByCategory($this->fetchLogs($user, $start, $end));
        $title = $this->buildTitle($type, WorkDailyReportExport::KIND_WORK, $start, $end);
        $previousOverview = $this->findPreviousOverview($userId, $type, WorkDailyReportExport::KIND_WORK, $start);

        return $this->buildSummaryMarkdown($title, $groups, $type, $model, $previousOverview);
    }

    public function exportData(WorkDailyReportExport $export): array
    {
        return [
            'id' => $export->id,
            'type' => $export->type,
            'kind' => $export->kind,
            'periodStart' => $export->period_start,
            'periodEnd' => $export->period_end,
            'model' => $export->model,
            'status' => $export->status,
            'fileName' => $export->file_name,
            'errorMessage' => $this->shortErrorMessage($export->error_message),
            'createdAt' => $export->created_at,
            'startedAt' => $export->started_at,
            'finishedAt' => $export->finished_at,
        ];
    }

    public function updateExportContent(WorkDailyReportExport $export, string $content): WorkDailyReportExport
    {
        $export->content = $content;
        $export->save();

        return $export;
    }

    public function renderHtml(WorkDailyReportExport $export): string
    {
        $title = preg_replace('/\.md$/u', '', (string)$export->file_name) ?: '工作报表';

        return $this->styledHtmlExportService->render($title, (string)$export->content);
    }

    public function resolveRange(string $type, array $payload): array
    {
        return match ($type) {
            'month' => [
                Carbon::createFromFormat('Y-m', (string)$payload['month'])->startOfMonth()->toDateString(),
                Carbon::createFromFormat('Y-m', (string)$payload['month'])->endOfMonth()->toDateString(),
            ],
            'week' => [
                Carbon::parse((string)$payload['start_date'])->toDateString(),
                Carbon::parse((string)$payload['end_date'])->toDateString(),
            ],
            'year' => [
                Carbon::createFromFormat('Y', (string)$payload['year'])->startOfYear()->toDateString(),
                Carbon::createFromFormat('Y', (string)$payload['year'])->endOfYear()->toDateString(),
            ],
            default => throw new \InvalidArgumentException('报表类型不正确'),
        };
    }

    private function fetchLogs(User $user, string $start, string $end): Collection
    {
        $query = WorkDailyLog::query()
            ->where('log_date', '>=', $start)
            ->where('log_date', '<=', $end)
            ->orderBy('log_date', 'asc')
            ->orderBy('id', 'asc');

        if (!$this->isManager($user)) {
            $query->where('create_user', $user->id);
        }

        return $query->get()->load('platform');
    }

    private function isManager(User $user): bool
    {
        foreach ($user->roles as $role) {
            if ($role->code === 'super') {
                return true;
            }
        }

        return false;
    }

    private function buildTitle(string $type, string $kind, string $start, string $end): string
    {
        if ($kind === WorkDailyReportExport::KIND_GROWTH) {
            return match ($type) {
                'month' => '个人成长月记 - ' . Carbon::parse($start)->format('Y-m'),
                'week' => "个人成长周记 - {$start} ~ {$end}",
                'year' => '个人成长年记 - ' . Carbon::parse($start)->format('Y'),
                default => throw new \InvalidArgumentException('报表类型不正确'),
            };
        }

        return match ($type) {
            'month' => '牛马日常月报 - ' . Carbon::parse($start)->format('Y-m'),
            'week' => "牛马日常周报 - {$start} ~ {$end}",
            'year' => '牛马日常年报 - ' . Carbon::parse($start)->format('Y'),
            default => '牛马日常报表',
        };
    }

    private function buildFileName(string $type, string $kind, array $payload): string
    {
        if ($kind === WorkDailyReportExport::KIND_GROWTH) {
            return match ($type) {
                'month' => '个人成长月记_' . $payload['month'] . '.md',
                'week' => '个人成长周记_' . $payload['start_date'] . '_' . $payload['end_date'] . '.md',
                'year' => '个人成长年记_' . $payload['year'] . '.md',
                default => throw new \InvalidArgumentException('报表类型不正确'),
            };
        }

        return match ($type) {
            'month' => '工作月报_' . $payload['month'] . '.md',
            'week' => '工作周报_' . $payload['start_date'] . '_' . $payload['end_date'] . '.md',
            'year' => '工作年报_' . $payload['year'] . '.md',
            default => '工作报表.md',
        };
    }

    /**
     * 按平台大类、再按平台名分组原始记录；未指定平台的记录归入工作大类。
     *
     * @param Collection $logs
     * @return array<string, array<string, array<int, array{date: string, content: string}>>>
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/30
     */
    private function groupRecordsByCategory(Collection $logs): array
    {
        $groups = array_fill_keys(WorkPlatform::CATEGORIES, []);
        $platformMap = $this->buildPlatformMap($logs);

        foreach ($logs as $item) {
            $platforms = $this->normalizePlatforms($item, $platformMap);
            if (empty($platforms)) {
                $content = is_array($item->content)
                    ? json_encode($item->content, JSON_UNESCAPED_UNICODE)
                    : $item->content;
                $platforms = [[
                    'platform_name' => $item->platform ? $item->platform->name : null,
                    'category' => $item->platform ? $item->platform->category : WorkPlatform::CATEGORY_WORK,
                    'content' => $content,
                ]];
            }

            foreach ($platforms as $platform) {
                $platformName = $platform['platform_name'] ?? '未指定平台';
                $groups[$platform['category']][$platformName][] = [
                    'date' => $item->log_date,
                    'content' => (string)($platform['content'] ?? ''),
                ];
            }
        }

        return $groups;
    }

    private function formatRecordSource(array $platformGroups, string $heading): string
    {
        $source = '';
        foreach ($platformGroups as $platform => $items) {
            $source .= "{$heading} {$platform}\n";
            foreach ($items as $item) {
                $source .= "- {$item['date']}: " . str_replace("\n", ' ', trim($item['content'])) . "\n";
            }
            $source .= "\n";
        }

        return $source;
    }

    private function countRecords(array $platformGroups): int
    {
        return array_sum(array_map('count', $platformGroups));
    }

    private function reportTypeLabel(string $type): string
    {
        return match ($type) {
            'month' => '月报',
            'week' => '周报',
            'year' => '年报',
            default => '报表',
        };
    }

    private function buildSummaryMarkdown(string $title, array $groups, string $type, ?string $model, ?string $previousOverview = null): string
    {
        $workGroups = $groups[WorkPlatform::CATEGORY_WORK];
        $studyGroups = $groups[WorkPlatform::CATEGORY_STUDY];
        if (empty($workGroups) && empty($studyGroups)) {
            return "# {$title}\n\n暂无记录。\n";
        }

        $source = '';
        if (!empty($workGroups)) {
            $source .= "# 工作\n\n" . $this->formatRecordSource($workGroups, '##');
        }
        if (!empty($studyGroups)) {
            $source .= "# 学习\n\n" . $this->formatRecordSource($studyGroups, '##');
        }

        $styleHint = match ($type) {
            'month' => '月报风格：强调本月完成模块、修复问题、体验改进、可见产出和月度学习模式。',
            'week' => '周报风格：强调本周重点进展、已解决问题、阻塞点和下周可延续动作。',
            'year' => '年终总结风格：抒情开篇，再依次讲工作、学习、用得顺手的地方、不足与新一年近期计划。',
            default => '按平台归纳总结，突出产出。',
        };

        // SKILL 是结构与规则的唯一权威，prompt 只塞输入变量，避免与 SKILL 漂移。
        $skill = $this->loadSkill('work-daily-report');
        $prompt = "请按下面注入的 work-daily-report skill 整理工作日志，输出中文 Markdown。\n" .
            "不要解释过程，只输出最终报表。\n\n" .
            "<skill name=\"work-daily-report\">\n{$skill}\n</skill>\n\n" .
            "报表输入：\n" .
            "- title: {$title}\n" .
            "- type: {$type}\n" .
            "- report_type_label: " . $this->reportTypeLabel($type) . "\n" .
            "- platform_count: " . (count($workGroups) + count($studyGroups)) . "\n" .
            "- record_count: " . ($this->countRecords($workGroups) + $this->countRecords($studyGroups)) . "\n" .
            "- work_platforms: " . (implode('、', array_keys($workGroups)) ?: '无') . "\n" .
            "- study_platforms: " . (implode('、', array_keys($studyGroups)) ?: '无') . "\n" .
            "- style: {$styleHint}\n\n" .
            ($previousOverview === null
                ? ''
                : "上一期报表概览（previous_summary，仅用于趋势对比）：\n{$previousOverview}\n\n") .
            "原始记录（已按大类、平台分组）：\n{$source}";

        return $this->normalizeSummaryMarkdown($title, $this->callReportModel($prompt, $model, 'work-daily-report'));
    }

    private function buildGrowthMarkdown(string $title, array $personalGroups, string $type, ?string $model, ?string $previousOverview = null): string
    {
        if (empty($personalGroups)) {
            return "# {$title}\n\n暂无记录。\n";
        }

        $skill = $this->loadSkill('personal-growth-report');
        $prompt = "请按下面注入的 personal-growth-report skill 整理个人项目记录，输出中文 Markdown。\n" .
            "不要解释过程，只输出最终成长记录。\n\n" .
            "<skill name=\"personal-growth-report\">\n{$skill}\n</skill>\n\n" .
            "成长记录输入：\n" .
            "- title: {$title}\n" .
            "- type: {$type}\n" .
            "- report_type_label: " . $this->reportTypeLabel($type) . "\n" .
            "- platform_count: " . count($personalGroups) . "\n" .
            "- record_count: " . $this->countRecords($personalGroups) . "\n" .
            "- platforms: " . implode('、', array_keys($personalGroups)) . "\n\n" .
            ($previousOverview === null
                ? ''
                : "上一期成长记录概览（previous_summary，仅用于趋势对比）：\n{$previousOverview}\n\n") .
            "原始记录（个人大类，已按平台分组）：\n" . $this->formatRecordSource($personalGroups, '##');

        return $this->normalizeSummaryMarkdown($title, $this->callReportModel($prompt, $model, 'personal-growth-report'));
    }

    public function findPreviousOverview(int $userId, string $type, string $kind, string $periodStart): ?string
    {
        $previous = WorkDailyReportExport::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('kind', $kind)
            ->where('status', WorkDailyReportExport::STATUS_COMPLETED)
            ->where('period_end', '<', $periodStart)
            ->orderBy('period_end', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$previous || trim((string)$previous->content) === '') {
            return null;
        }

        return $this->extractOverview((string)$previous->content);
    }

    public function extractOverview(string $markdown): ?string
    {
        // 兼容带图标的新格式（## 🏝️ 概览）、无图标的历史格式（## 概览）和年终总结的开篇（## 🎐 写在前面）
        if (!preg_match('/^##\s[^\n]*(?:概览|写在前面)[^\n]*$\R(.*?)(?=^#{1,6}\s|\z)/msu', $markdown, $matches)) {
            return null;
        }

        $overview = trim($matches[1]);
        if ($overview === '') {
            return null;
        }

        return mb_substr($overview, 0, 600);
    }

    private function loadSkill(string $name): string
    {
        $path = resource_path("ai/skills/{$name}/SKILL.md");
        if (!is_file($path)) {
            throw new \RuntimeException("{$name} skill file not found");
        }

        $skill = trim((string)file_get_contents($path));
        if ($skill === '') {
            throw new \RuntimeException("{$name} skill file is empty");
        }

        return $skill;
    }

    private function normalizeSummaryMarkdown(string $title, string $summary): string
    {
        $summary = trim($summary);
        if (preg_match('/^#\s+/u', $summary) === 1) {
            return $summary . "\n";
        }

        return "# {$title}\n\n{$summary}\n";
    }

    /**
     * 查询记录涉及平台的名称和大类。
     *
     * @param Collection $logs
     * @return array<int, array{name: string, category: string}>
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/30
     */
    private function buildPlatformMap(Collection $logs): array
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
            ->get(['id', 'name', 'category'])
            ->mapWithKeys(fn(WorkPlatform $platform) => [
                $platform->id => ['name' => $platform->name, 'category' => $platform->category],
            ])
            ->all();
    }

    private function normalizePlatforms(WorkDailyLog $item, array $platformMap): array
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
            $mapped = $platformId ? ($platformMap[$platformId] ?? null) : null;
            $platforms[] = [
                'platform_id' => $platformId,
                'platform_name' => $platform['platform_name'] ?? ($mapped['name'] ?? null),
                'category' => $mapped['category'] ?? WorkPlatform::CATEGORY_WORK,
                'content' => $platform['content'] ?? '',
            ];
        }

        return $platforms;
    }

    private function callReportModel(string $prompt, ?string $model, string $skillName): string
    {
        $targetModel = $model ?: env('OPENCLAW_MODEL', 'github-copilot/gpt-5.2-codex');

        if ($this->isLocalCodexModel($targetModel)) {
            return $this->callLocalCodex($this->applyHumanWritingSkill($prompt, $skillName), $targetModel);
        }

        if ($this->isLocalAgyModel($targetModel)) {
            return $this->callLocalAgy($this->applyHumanWritingSkill($prompt, $skillName), $targetModel);
        }

        if ($this->isLocalClaudeModel($targetModel)) {
            return $this->callLocalClaude($this->applyHumanWritingSkill($prompt, $skillName), $targetModel);
        }

        return $this->callOpenClaw($prompt, $targetModel);
    }

    /**
     * 为本机 Codex / Claude / Gemini 报表追加活人感写作 skill 指令。
     *
     * @param string $prompt
     * @param string $skillName 负责结构与事实边界的报表 skill
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/30
     */
    private function applyHumanWritingSkill(string $prompt, string $skillName): string
    {
        $factSource = $skillName === 'personal-growth-report'
            ? '原始记录和注入的个人能力档案是唯一事实来源。'
            : '原始工作记录是唯一事实来源。';

        return "使用 \$human-writing 对报表成稿进行中文创作与改稿。\n" .
            "{$skillName} skill 负责报表结构、Markdown 格式、统计口径和事实边界；" .
            "human-writing skill 只负责自然中文和成稿复核。\n" .
            $factSource . "禁止检索、追问或补造材料；资料不足时按报表 skill 的精简规则输出。\n" .
            "两套规则冲突时，以 {$skillName} skill 为准。不要解释过程，只输出最终 Markdown。\n\n" .
            $prompt;
    }

    private function callOpenClaw(string $prompt, ?string $model = null): string
    {
        $baseUrl = $this->resolveOpenClawGatewayUrl();
        if (!$baseUrl) {
            throw new \RuntimeException('OPENCLAW_GATEWAY_URL 未配置');
        }

        $targetModel = $model ?: env('OPENCLAW_MODEL', 'github-copilot/gpt-5.2-codex');
        $token = env('OPENCLAW_GATEWAY_TOKEN');

        if (str_starts_with($targetModel, 'bailian/')) {
            return $this->callBailianDirect($prompt, $targetModel);
        }

        $headers = [];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        $response = Http::withHeaders($headers)->post($baseUrl . '/v1/chat/completions', [
            'model' => $targetModel,
            'temperature' => 0.2,
            'messages' => [
                ['role' => 'system', 'content' => '你是一个擅长按平台归纳工作日志的助手，输出中文 Markdown。'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (!$response->ok()) {
            Log::warning('OpenClaw summary failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('OpenClaw summary failed: ' . $response->status() . ' ' . $this->extractResponseError($response->body()));
        }

        $content = $response->json('choices.0.message.content');
        if (is_string($content) && str_starts_with($content, 'LLM request rejected:')) {
            Log::warning('OpenClaw summary rejected', [
                'model' => $targetModel,
                'content' => $content,
            ]);
            throw new \RuntimeException($content);
        }

        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('OpenClaw summary returned empty content');
        }

        return $content;
    }

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
                ->timeout(1770)
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
            throw new \RuntimeException('Local Codex summary failed: ' . $response->status() . ' ' . $this->extractResponseError($response->body()));
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Local Codex summary returned empty content');
        }

        return $content;
    }

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
                ->timeout(1770)
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
            throw new \RuntimeException('Local Agy summary failed: ' . $response->status() . ' ' . $this->extractResponseError($response->body()));
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
                ->timeout(1770)
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
            throw new \RuntimeException('Local Claude summary failed: ' . $response->status() . ' ' . $this->extractResponseError($response->body()));
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Local Claude summary returned empty content');
        }

        return $content;
    }

    private function callBailianDirect(string $prompt, string $model): string
    {
        $apiKey = env('OPENCLAW_BAILIAN_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException('OPENCLAW_BAILIAN_API_KEY 未配置');
        }

        $modelId = preg_replace('/^bailian\//', '', $model);
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
            throw new \RuntimeException('Bailian summary failed: ' . $response->status() . ' ' . $this->extractResponseError($response->body()));
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Bailian summary returned empty content');
        }

        return $content;
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

    private function resolveLocalAgyBridgeUrl(): ?string
    {
        $baseUrl = config('services.local_agy.bridge_url');

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

    private function extractResponseError(string $body): string
    {
        $data = json_decode($body, true);
        if (is_array($data)) {
            $message = $data['error']['message'] ?? $data['message'] ?? null;
            if (is_string($message) && trim($message) !== '') {
                return $this->shortErrorMessage($message);
            }
        }

        return $this->shortErrorMessage($body);
    }

    private function shortErrorMessage(?string $message): ?string
    {
        if (!is_string($message) || trim($message) === '') {
            return null;
        }

        $message = trim(preg_replace('/\s+/', ' ', $message));
        if (str_contains($message, "You've hit your usage limit")) {
            return '本机 Codex CLI 用量已达上限，请稍后再试。';
        }

        return mb_substr($message, 0, 200);
    }
}
