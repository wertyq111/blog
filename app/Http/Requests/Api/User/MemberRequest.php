<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;
use App\Rules\AvatarUrl;
use Illuminate\Support\Facades\Validator;

class MemberRequest extends FormRequest
{

    public function rules(): array
    {
        switch ($this->method()) {
            case 'POST':
                return [
                    'avatar' => new AvatarUrl()
                ];
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
            'avatar' => '头像'
        ];
    }
}
