<?php

namespace App\Models\MiniProgram;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WallpaperClassify extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'pic_url',
        'select',
        'sort'
    ];


    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'select' => [
            'column' => 'select',
            'filterType' => 'exact'
        ],
        'name' => [
            'column' => 'name'
        ]
    ];

    /**
     * 一对多关联
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 10:51
     */
    public function wallpapers()
    {
        return $this->hasMany(Wallpaper::class, 'class_id');
    }
}
