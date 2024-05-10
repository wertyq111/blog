<?php

namespace App\Models\Web;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Label extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name', 'category_id', 'description'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/10 16:40
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 一对多关联
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 10:51
     */
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
