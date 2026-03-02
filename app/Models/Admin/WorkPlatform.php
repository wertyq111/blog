<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkPlatform extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'sort',
    ];

    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'name' => ['column' => 'name'],
        'status' => ['column' => 'status', 'filterType' => 'exact'],
    ];
}
