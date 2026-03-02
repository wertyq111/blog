<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkDailyLog extends BaseModel
{
    use HasFactory;

    protected $table = 'work_daily_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'platform_id',
        'log_date',
        'content',
    ];

    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'platform_id' => ['column' => 'platform_id', 'filterType' => 'exact'],
        'log_date' => ['column' => 'log_date', 'filterType' => 'exact'],
    ];

    /**
     * 平台关联
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function platform()
    {
        return $this->belongsTo(WorkPlatform::class, 'platform_id');
    }
}
