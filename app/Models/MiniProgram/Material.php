<?php

namespace App\Models\MiniProgram;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Material extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'pid',
        'name',
        'style',
        'num'
    ];


    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'memberId' => [
            'column' => 'member_id',
            'filterType' => 'exact'
        ],
        'pid' => [
            'column' => 'pid',
            'filterType' => 'exact'
        ],
        'houseId' => [
            'column' => 'house_id',
            'filterType' => 'exact'
        ],
        'name' => ['column' => 'name']
    ];

    /**
     * 一对多关联
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 10:51
     */
    public function child()
    {
        return $this->hasMany(self::class,'pid');
    }

    /**
     * 递归子级
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/7 17:07
     */
    public function children()
    {
        return $this->child()->with('children');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/7 17:07
     */
    public function parent()
    {
        return $this->hasMany(self::class,'id','pid');
    }

    /**
     * 递归父级
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/7 17:07
     */
    public function parents()
    {
        return $this->parent()->with('parents');
    }

    /**
     * 所属房屋
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/18 09:55
     */
    public function house()
    {
        return $this->belongsTo(House::class);
    }
}
