<?php

namespace App\Models\MiniProgram;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallpaper extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'class_id',
        'url',
        'description',
        'small_pic_url',
        'score',
        'nickname',
        'tags'
    ];


    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'classId' => [
            'column' => 'class_id',
            'filterType' => 'exact'
        ],
        'nickname' => ['column' => 'nickname'],
        'download' => [
            'column' => 'downloads.member_id',
            'filterType' => 'exact'
        ],
        'score' => [
            'column' => 'scores.member_id',
            'filterType' => 'exact'
        ],
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 17:25
     */
    public function classify()
    {
        return $this->belongsTo(WallpaperClassify::class, 'class_id');
    }

    /**
     * 下载列表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:13
     */
    public function downloads()
    {
        return $this->hasMany(WallpaperDownload::class);
    }

    /**
     * 评分列表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:13
     */
    public function scores()
    {
        return $this->hasMany(WallpaperScore::class);
    }
}
