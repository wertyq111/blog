<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use App\Models\MiniProgram\WallpaperClassify;
use App\Models\MiniProgram\WallpaperDownload;
use App\Models\MiniProgram\WallpaperScore;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InitModel extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'template',
        'tip'
    ];


    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'code' => ['column' => 'code',],
        'name' => ['column' => 'name'],
    ];
}
