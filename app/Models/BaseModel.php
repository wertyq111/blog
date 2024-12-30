<?php

namespace App\Models;

use App\Models\Traits\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Authenticatable
{

    // 使用软删除
    use SoftDeletes;


    // 默认使用时间戳戳功能
    public $timestamps = true;

    /**
     * 请求过滤数组
     * @var null
     */
    protected $requestFilters = null;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
        'created_at',
        'create_user',
        'updated_at',
        'update_user'
    ];

    /**
     * 获取当前时间
     * @return int 时间戳
     * @author vwms@netsun.com
     * @date 2019/5/30
     */
    public function freshTimestamp()
    {
        return time();
    }

    /**
     * 避免转换时间戳为时间字符串
     * @param mixed $value 时间
     * @return mixed|string|null
     * @author vwms@netsun.com
     * @date 2019/5/30
     */
    public function fromDateTime($value)
    {
        return $value;
    }

    /**
     * 获取时间戳格式
     * @return string 时间戳字符串格式
     * @author vwms@netsun.com
     * @date 2019/5/30
     */
    public function getDateFormat()
    {
        return 'U';
    }

    /**
     * 添加/修改记录是填充额外属性
     *
     * @param $userOperate //是否用户操作
     * @return bool
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/25 15:09
     */
    public function edit($userOperate = true)
    {
        if($userOperate) {
            $tableName = $this->getTable();
            // 如果对应的数据表中定义了create_user并且不存在 id 则填充创建者 id
            if(!$this->id) {
                if(Schema::hasColumn($tableName, 'create_user')) {
                    $this->create_user = Auth('api')->user()->id ?? 0;
                }
                if(Schema::hasColumn($tableName, 'update_user')) {
                    $this->update_user = Auth('api')->user()->id ?? 0;
                }
            }

            // 如果对应的数据表中定义了update_user并且存在 id 则填充修改者 id
            if($this->id > 0 && Schema::hasColumn($tableName, 'update_user')) {
                $this->update_user = Auth('api')->user()->id;
            }
        }

        return $this->save();
    }

    /**
     * 外部调用过滤参数数组
     *
     * @return string[]
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/14 09:04
     */
    public function getRequestFilters()
    {
        return $this->requestFilters;
    }
}
