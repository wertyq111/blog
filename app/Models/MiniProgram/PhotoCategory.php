<?php

namespace App\Models\MiniProgram;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhotoCategory extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'member_id',
        'name',
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/29 14:03
     */
    public function photos()
    {
        return $this->hasMany(Photo::class, 'category_id');
    }
}
