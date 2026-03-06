<?php

namespace App\Models\MiniProgram;

use App\Models\BaseModel;
use App\Models\User\Member;
use App\Services\Api\QiniuService;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Photo extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'category_id',
        'url',
        'remark',
        'show'
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
        'categoryId' => [
            'column' => 'category_id',
            'filterType' => 'exact'
        ]
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/29 13:52
     */
    public function category()
    {
        return $this->belongsTo(PhotoCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/7/3 13:25
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function photo()
    {
        $qiniuService = new QiniuService();
    }
}
