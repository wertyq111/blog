<?php

namespace App\Models\Tobacco;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TobaccoYun extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer',
        'number',
        'settle_date'
    ];


    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'customer' => [
            'column' => 'customer',
            'filterType' => 'exact'
        ],
        'settle_date' => ['column' => 'settle_date']
    ];

    /**
     * 客户
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 17:07
     */
    public function customer()
    {
        return $this->belongsTo(TobaccoCustomer::class);
    }
}
