<?php

namespace App\Http\Requests\Api\Admin;


use App\Http\Requests\Api\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        switch ($this->method()) {
            case 'POST':
                list($class, $method) = explode('@', $this->route()->getActionName());
                if($method == 'add') {
                    return [
                        'name' => [
                            'required',
                            'between:3,25',
                            Rule::unique('roles')->where(function ($query) {
                                $query->where('deleted_at', 0);
                            })
                        ],
                        'code' => 'required|string|min:3'
                    ];
                }
            case 'PATCH':
                return [
                    'status' => 'boolean',
                ];
        }

        return [];
    }
}
