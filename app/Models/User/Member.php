<?php

namespace App\Models\User;

use App\Models\BaseModel;
use App\Models\MiniProgram\WallpaperDownload;
use App\Models\MiniProgram\WallpaperScore;
use App\Models\Web\Article;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'member_level',
        'realname',
        'nickname',
        'gender',
        'avatar',
        'birthday',
        'province_code',
        'city_code',
        'district_code',
        'address',
        'intro',
        'signature',
        'admire',
        'device',
        'source',
        'status',
        'app_version',
        'code',
        'login_ip',
        'login_at',
        'login_region',
        'login_count'
    ];

    /**
     * 过滤参数配置
     *
     * @var array[]
     */
    protected $requestFilters = [
        'username' => ['column' => 'user.username'],
        'gender' => ['column' => 'gender', 'filterType' => 'exact'],
        'nickname' => ['column' => 'nickname']
    ];

    /**
     *  一对一关联(反向)
     *  @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 下载列表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:13
     */
    public function downloads()
    {
        return $this->hasMany(WallpaperDownload::class);
    }

    /**
     * 评分列表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/15 14:13
     */
    public function scores()
    {
        return $this->hasMany(WallpaperScore::class);
    }

    /**
     * 点赞数
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/14 15:52
     */
    public function goods() {
        return $this->belongsToMany(Article::class, 'article_member_goods');
    }
}
