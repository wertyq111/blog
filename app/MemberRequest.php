<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;
use App\Rules\AvatarUrl;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    protected function currentActionMethod(): ?string
    {
        $actionName = $this->route()?->getActionName();
        if (!$actionName || !str_contains($actionName, '@')) {
            return null;
        }

        [, $method] = explode('@', $actionName);

        return $method;
    }

    public function rules(): array
    {
        switch ($this->method()) {
            case 'POST':
                $method = $this->currentActionMethod();

                if ($method === 'status') {
                    return [];
                }

                $rules = [
                    'avatar' => ['nullable', new AvatarUrl()]
                ];

                if ($method === 'add') {
                    $rules['user_id'] = [
                        'required',
                        'integer',
                        Rule::unique('members')->where(function ($query) {
                            $query->where('deleted_at', 0);
                        })
                    ];
                }

                return $rules;
            case 'PATCH':
                return [
                    'admire' => 'decimal:0,2'
                ];
            default:
                return [];
        }
    }

    public function attributes()
    {
        return [
            'avatar' => '头像',
            'user_id' => '用户会员'
        ];
    }
}
