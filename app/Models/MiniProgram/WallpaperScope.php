<?php

namespace App\Models\MiniProgram;

use App\Models\BaseModel;
use App\Models\User\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WallpaperScope extends BaseModel
{
    use HasFactory;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 10:51
     */
    public function wallpaper()
    {
        return $this->belongsTo(Wallpaper::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:12
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
