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
     * 平台关联（保留兼容）
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function platform()
    {
        return $this->belongsTo(WorkPlatform::class, 'platform_id');
    }

    /**
     * content 字段 accessor：返回解码的数组（如果是 JSON 则解码，否则返回原始字符串）
     */
    public function getContentAttribute($value)
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // 兼容旧格式：将原始文本包装成单个平台的内容
        return [
            'platforms' => [
                [
                    'platform_id' => $this->platform_id,
                    'platform_name' => $this->platform ? $this->platform->name : null,
                    'content' => $value,
                ]
            ]
        ];
    }

    /**
     * content 字段 mutator：接受数组或字符串，保存为 JSON 字符串
     */
    public function setContentAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['content'] = json_encode($value, JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->attributes['content'] = $value;
    }

    /**
     * 查找用户某日的记录
     */
    public static function findByUserAndDate($userId, $date)
    {
        return self::where('create_user', $userId)->where('log_date', $date)->first();
    }
}
