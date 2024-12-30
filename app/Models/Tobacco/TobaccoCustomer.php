<?php

namespace App\Models\Tobacco;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TobaccoCustomer extends BaseModel
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
        'stage'
    ];


    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'stage' => [
            'column' => 'stage',
            'filterType' => 'exact'
        ],
        'code' => ['column' => 'code'],
        'name' => ['column' => 'name']
    ];

    /**
     * 本次订货数据
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 17:07
     */
    public function orders()
    {
        return $this->hasMany(TobaccoOrders::class);
    }

    /**
     * 1024定点供货
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 17:09
     */
    public function designateds()
    {
        return $this->hasMany(TobaccoDesignated::class);
    }

    /**
     * 补供供货明细
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 17:10
     */
    public function supplements()
    {
        return $this->hasMany(TobaccoSupplement::class);
    }

    /**
     * 云烟（硬云龙）补供明细
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 17:11
     */
    public function yuns()
    {
        return $this->hasMany(TobaccoYun::class);
    }
}
