<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    /**
     * @var bool
     */
    protected $showSensitiveFields = true;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->showSensitiveFields) {
            $this->resource->makeHidden(['phone', 'email', 'openid', 'unionid']);
        }

        $data = parent::toArray($request);

        $data['createTime'] = $this->diffDateTime($this->created_at) ? (string) $this->created_at : null;
        $data['updateTime'] = $this->diffDateTime($this->updated_at) ? (string) $this->updated_at : null;
        $data['deleteTime'] = $this->diffDateTime($this->deleted_at) ? (string) $this->deleted_at : null;
        $data['member'] = new MemberResource($this->whenLoaded('member'));
        $data['roles'] = $this->whenLoaded('roles', function () {
            return $this->roles->toArray();
        });
        $data['permissions'] = $this->permissions();

        return $data;
    }

    /**
     * 显示隐藏字段
     *
     * @return $this
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/8 11:16
     */
    public function showSensitiveFields()
    {
        $this->showSensitiveFields = true;

        return $this;
    }
}
