<?php

namespace App\Models\Tobacco;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TobaccoSupplement extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer',
        'number1',
        'number2',
        'number3',
        'number4',
        'number5',
        'number6',
        'number7',
        'number8',
        'number9',
        'number10',
        'number11',
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

    /**
     * 获取合计数
     *
     * @return int
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/30 09:02
     */
    public function getTotalNumber()
    {
        $totalNumber = 0;

        foreach($this->toArray() as $key => $val) {
            if(strstr($key, "number")) {
                $totalNumber += $val;
            }
        }

        return $totalNumber;
    }
}
