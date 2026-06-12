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
use Illuminate\Support\Str;

class WorkDailyReportService
{
    public function createExport(int $userId, string $type, array $payload, ?string $model): WorkDailyReportExport
    {
        [$start, $end] = $this->resolveRange($type, $payload);
        $fileName = $this->buildFileName($type, $payload);

        $export = new WorkDailyReportExport();
        $export->fill([
            'user_id' => $userId,
            'type' => $type,
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
        $logs = $this->fetchLogs($user, $export->period_start, $export->period_end);
        $title = $this->buildTitle($export->type, $export->period_start, $export->period_end);
        $previousOverview = $this->findPreviousOverview($export->user_id, $export->type, $export->period_start);

        return $this->buildSummaryMarkdown($title, $logs, $export->type, $export->model, $previousOverview);
    }

    public function generateForUser(int $userId, string $type, array $payload, ?string $model): string
    {
        [$start, $end] = $this->resolveRange($type, $payload);
        $user = User::query()->with('roles')->findOrFail($userId);
        $logs = $this->fetchLogs($user, $start, $end);
        $title = $this->buildTitle($type, $start, $end);
        $previousOverview = $this->findPreviousOverview($userId, $type, $start);

        return $this->buildSummaryMarkdown($title, $logs, $type, $model, $previousOverview);
    }

    public function exportData(WorkDailyReportExport $export): array
    {
        return [
            'id' => $export->id,
            'type' => $export->type,
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
        $body = Str::markdown((string)$export->content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $title = preg_replace('/\.md$/u', '', (string)$export->file_name) ?: '工作报表';

        return view('exports.work-daily-report', ['title' => $title, 'body' => $body])->render();
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

    private function buildTitle(string $type, string $start, string $end): string
    {
        return match ($type) {
            'month' => '牛马日常月报 - ' . Carbon::parse($start)->format('Y-m'),
            'week' => "牛马日常周报 - {$start} ~ {$end}",
            'year' => '牛马日常年报 - ' . Carbon::parse($start)->format('Y'),
            default => '牛马日常报表',
        };
    }

    private function buildFileName(string $type, array $payload): string
    {
        return match ($type) {
            'month' => '工作月报_' . $payload['month'] . '.md',
            'week' => '工作周报_' . $payload['start_date'] . '_' . $payload['end_date'] . '.md',
            'year' => '工作年报_' . $payload['year'] . '.md',
            default => '工作报表.md',
        };
    }

    private function buildSummaryMarkdown(string $title, Collection $logs, string $type, ?string $model, ?string $previousOverview = null): string
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
                    'content' => $content,
                ];
                continue;
            }

            foreach ($platforms as $platform) {
                $platformName = $platform['platform_name'] ?? ($platformNameMap[$platform['platform_id']] ?? '未指定平台');
                $platformGroups[$platformName][] = [
                    'date' => $item->log_date,
                    'content' => $platform['content'] ?? '',
                ];
            }
        }

        $source = '';
        $recordCount = 0;
        foreach ($platformGroups as $platform => $items) {
            $source .= "## {$platform}\n";
            foreach ($items as $item) {
                $recordCount++;
                $source .= "- {$item['date']}: " . str_replace("\n", ' ', trim((string)$item['content'])) . "\n";
            }
            $source .= "\n";
        }

        $typeLabel = match ($type) {
            'month' => '月报',
            'week' => '周报',
            'year' => '年报',
            default => '报表',
        };
        $platformNames = implode('、', array_keys($platformGroups));
        $styleHint = match ($type) {
            'month' => '月报风格：强调本月完成模块、修复问题、体验改进、可见产出和月度学习模式。',
            'week' => '周报风格：强调本周重点进展、已解决问题、阻塞点和下周可延续动作。',
            'year' => '年报风格：强调年度成果、关键项目、长期改进、经验沉淀和下一年方向。',
            default => '按平台归纳总结，突出产出。',
        };

        // SKILL 是结构与规则的唯一权威，prompt 只塞输入变量，避免与 SKILL 漂移。
        $skill = $this->loadReportSkill();
        $prompt = "请按下面注入的 work-daily-report skill 整理工作日志，输出中文 Markdown。\n" .
            "不要解释过程，只输出最终报表。\n\n" .
            "<skill name=\"work-daily-report\">\n{$skill}\n</skill>\n\n" .
            "报表输入：\n" .
            "- title: {$title}\n" .
            "- type: {$type}\n" .
            "- report_type_label: {$typeLabel}\n" .
            "- platform_count: " . count($platformGroups) . "\n" .
            "- record_count: {$recordCount}\n" .
            "- platforms: {$platformNames}\n" .
            "- style: {$styleHint}\n\n" .
            ($previousOverview === null
                ? ''
                : "上一期报表概览（previous_summary，仅用于趋势对比）：\n{$previousOverview}\n\n") .
            "原始记录（已按平台分组）：\n{$source}";

        return $this->normalizeSummaryMarkdown($title, $this->callReportModel($prompt, $model));
    }

    public function findPreviousOverview(int $userId, string $type, string $periodStart): ?string
    {
        $previous = WorkDailyReportExport::query()
            ->where('user_id', $userId)
            ->where('type', $type)
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
        // 兼容带图标的新格式（## 🏝️ 概览）和无图标的历史格式（## 概览）
        if (!preg_match('/^##\s[^\n]*概览[^\n]*$\R(.*?)(?=^#{1,6}\s|\z)/msu', $markdown, $matches)) {
            return null;
        }

        $overview = trim($matches[1]);
        if ($overview === '') {
            return null;
        }

        return mb_substr($overview, 0, 600);
    }

    private function loadReportSkill(): string
    {
        $path = resource_path('ai/skills/work-daily-report/SKILL.md');
        if (!is_file($path)) {
            throw new \RuntimeException('work-daily-report skill file not found');
        }

        $skill = trim((string)file_get_contents($path));
        if ($skill === '') {
            throw new \RuntimeException('work-daily-report skill file is empty');
        }

        return $skill;
    }

    private function buildMarkdown(string $title, Collection $logs): string
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

    private function normalizeSummaryMarkdown(string $title, string $summary): string
    {
        $summary = trim($summary);
        if (preg_match('/^#\s+/u', $summary) === 1) {
            return $summary . "\n";
        }

        return "# {$title}\n\n{$summary}\n";
    }

    private function buildPlatformNameMap(Collection $logs): array
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
                'content' => $platform['content'] ?? '',
            ];
        }

        return $platforms;
    }

    private function callReportModel(string $prompt, ?string $model = null): string
    {
        $targetModel = $model ?: env('OPENCLAW_MODEL', 'github-copilot/gpt-5.2-codex');

        if ($this->isLocalCodexModel($targetModel)) {
            return $this->callLocalCodex($prompt, $targetModel);
        }

        if ($this->isLocalGeminiModel($targetModel)) {
            return $this->callLocalGemini($prompt, $targetModel);
        }

        if ($this->isLocalClaudeModel($targetModel)) {
            return $this->callLocalClaude($prompt, $targetModel);
        }

        return $this->callOpenClaw($prompt, $targetModel);
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
            throw new \RuntimeException('Local Codex summary failed: ' . $response->status() . ' ' . $this->extractResponseError($response->body()));
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Local Codex summary returned empty content');
        }

        return $content;
    }

    private function callLocalGemini(string $prompt, string $model): string
    {
        $baseUrl = $this->resolveLocalGeminiBridgeUrl();
        if (!$baseUrl) {
            throw new \RuntimeException('LOCAL_GEMINI_BRIDGE_URL 未配置');
        }

        $headers = [];
        $token = config('services.local_gemini.bridge_token');
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
            throw new \RuntimeException($this->bridgeUnreachableMessage('Gemini'), 0, $e);
        }

        if (!$response->ok()) {
            throw new \RuntimeException('Local Gemini summary failed: ' . $response->status() . ' ' . $this->extractResponseError($response->body()));
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Local Gemini summary returned empty content');
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

    private function resolveLocalGeminiBridgeUrl(): ?string
    {
        $baseUrl = config('services.local_gemini.bridge_url');

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

    private function isLocalGeminiModel(string $model): bool
    {
        return str_starts_with($model, 'local-gemini/');
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
