<?php

namespace App\Models\Web;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Label extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name', 'description'
    ];

    /**
     * 一对一关联
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 10:54
     */
    public function category()
    {
        $this->belongsTo(Category::class);
    }
}
