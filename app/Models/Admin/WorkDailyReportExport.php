<?php

namespace App\Models\Admin;

use App\Models\BaseModel;

class WorkDailyReportExport extends BaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const KIND_WORK = 'work';
    public const KIND_GROWTH = 'growth';

    protected $table = 'work_daily_report_exports';

    protected $fillable = [
        'user_id',
        'type',
        'kind',
        'period_start',
        'period_end',
        'model',
        'status',
        'file_name',
        'content',
        'error_message',
        'started_at',
        'finished_at',
    ];

    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
        ];
    }

    /**
     * 报表任务的整体超时秒数：年报记录多、max 推理耗时长，单独放宽到 60 分钟。
     *
     * @param string $type
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/9/30
     */
    public static function timeoutFor(string $type): int
    {
        return $type === 'year' ? 3600 : 1800;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
