<?php

namespace App\Models\Web;

use App\Models\BaseModel;
use App\Models\User\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Article extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'category_id',
        'label_id',
        'cover',
        'view_status',
        'password',
        'recommend_status',
        'comment_status'
    ];

    /**
     * 过滤参数配置
     *
     * @var string[]
     */
    protected $requestFilters = [
        'title' => ['column' => 'title'],
        'categoryId' => ['column' => 'category.id'],
        'labelId' => ['column' => 'label.id'],
        'viewStatus' => [
            'column' => 'view_status',
            'filterType' => 'exact'
        ],
        'recommendStatus' => [
            'column' => 'recommend_status',
            'filterType' => 'exact'
        ],
        'commentStatus' => [
            'column' => 'comment_status',
            'filterType' => 'exact'
        ]
    ];

    /**
     * 外部调用过滤参数数组
     *
     * @return string[]
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/14 09:04
     */
    public function getRequestFilters()
    {
        return $this->requestFilters;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 17:23
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 17:25
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 17:25
     */
    public function label()
    {
        return $this->belongsTo(Label::class);
    }
}
