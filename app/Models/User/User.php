<?php

namespace App\Models\User;

use App\Models\BaseModel;
use App\Models\Permission\Menu;
use App\Models\Permission\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends BaseModel implements MustVerifyEmail, JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, MustVerifyEmailTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'phone',
        'email',
        'openid',
        'unionid',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * 一对一关联(正向)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/6 13:35
     */
    public function member()
    {
        return $this->hasOne(Member::class);
    }

    /**
     * 多对多
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/8 10:27
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * 权限列表
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 15:40
     */
    public function permissions()
    {
        $permissions = [];
        if(count($this->roles) > 0) {
            foreach($this->roles as $role) {
                // 超级管理员获取全部权限
                if($role->code === 'super') {
                    $menus = (new Menu())->all();
                    $permissions = array_filter(array_unique(array_column($menus->toArray(), 'permission')));
                    break;
                } else {
                    $array_column = array_filter(array_unique(array_column($role->menus->toArray(), 'permission')));
                    $permissions = array_merge($permissions, $array_column);
                }
            }
        }

        return array_unique($permissions);
    }

    /**
     * 验证登录用户
     *
     * @param $model
     * @return bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/17 15:40
     */
    public function isAuthorOf($model)
    {
        return $this->member->id == $model->member_id;
    }
}
