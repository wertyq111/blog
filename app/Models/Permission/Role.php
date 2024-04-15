<?php

namespace App\Models\Permission;

use App\Models\BaseModel;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends BaseModel
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
        'sort',
        'status',
        'note'
    ];


    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'code' => [
            'column' => 'code',
            'filterType' => 'exact'
        ],
        'name' => ['column' => 'name']
    ];

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

    /**
     * 多对多
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/8 10:27
     */
    public function menus()
    {
        return $this->belongsToMany(Menu::class);
    }
}
