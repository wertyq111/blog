<?php

namespace App\Models\Admin;

use App\Models\BaseModel;

class WorkDailyTag extends BaseModel
{
    protected $table = 'work_daily_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'name' => ['column' => 'name', 'filterType' => 'like'],
    ];

    /**
     * 限定当前用户可见标签（系统预设 + 自己创建的）
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('create_user', 0)->orWhere('create_user', $userId);
        });
    }

    /**
     * 关联日志
     */
    public function logs()
    {
        return $this->belongsToMany(WorkDailyLog::class, 'work_daily_log_tag');
    }
}
