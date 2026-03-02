<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkPlatform extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'sort',
        'user_id',
    ];

    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'name' => ['column' => 'name'],
        'status' => ['column' => 'status', 'filterType' => 'exact'],
    ];

    /**
     * 按用户范围过滤（非 super 仅显示自己的）
     */
    public function scopeForUser($query, $user)
    {
        if (isset($user->role) && $user->role === 'super') {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
