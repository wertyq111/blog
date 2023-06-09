<?php

namespace App\Models\User;

use App\Models\BaseModel;
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
        'password',
        'statue',
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
}
