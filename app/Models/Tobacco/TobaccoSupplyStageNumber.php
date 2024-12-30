<?php

namespace App\Models\Tobacco;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TobaccoSupplyStageNumber extends Model
{
    use HasFactory;

    const CREATED_AT = null;

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supply',
        'stage',
        'number'
    ];

    /**
     * 一对多关联
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 10:51
     */
    public function supplies()
    {
        return $this->hasMany(TobaccoSupply::class, 'supply', 'id');
    }

}
