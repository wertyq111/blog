<?php

namespace App\Models\Tobacco;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TobaccoSupply extends BaseModel
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
        'remark',
        'settle_date'
    ];


    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'code' => ['column' => 'code'],
        'name' => ['column' => 'name'],
        'settle_date' => ['column' => 'settle_date']
    ];

    /**
     * 档位数量信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/27 09:57
     */
    public function stages()
    {
        return $this->belongsToMany(TobaccoStage::class, 'tobacco_supply_stage_numbers')->withPivot('number');
    }
}
