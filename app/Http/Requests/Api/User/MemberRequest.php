<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\FormRequest;

class MemberRequest extends FormRequest
{

    public function rules(): array
    {
        switch ($this->method()) {
            case 'POST':
                return [

                ];
            case 'PATCH':
                return [
                    'admire' => 'decimal:0,2'
                ];
            default:
                return [];
        }
    }
}
