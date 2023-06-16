<?php

namespace App\Models\Web;

use App\Models\BaseModel;
use App\Models\User\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'source',
        'type',
        'comment_id',
        'parent_id',
        'like_count',
        'content',
        'info'
    ];

    /**
     * 过滤参数配置
     *
     * @var string[]
     */
    protected $requestFilters = [
        'type' => [
            'column' => 'type',
            'filterType' => 'exact'
        ],
        'source' => [
            'column' => 'source',
            'filterType' => 'exact'
        ],
        'commentId' => [
            'column' => 'comment_id',
            'filterType' => 'exact'
        ]
    ];

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
     * 父级评论
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 14:33
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * 子级评论
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 14:35
     */
    public function children()
    {
        return $this->hasMany(Comment::class);
    }

}
