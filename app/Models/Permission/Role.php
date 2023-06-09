<?php

namespace App\Models\Permission;

use App\Models\BaseModel;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends BaseModel
{
    use HasFactory;

    /**
     * 多对多
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/8 10:27
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
