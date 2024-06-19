<?php

namespace App\Http\Requests\Api\MiniProgram;


use App\Http\Requests\Api\FormRequest;

class HouseRequest extends FormRequest
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
                        'name' => 'required|string|min:2',
                    ];
                }
        }

        return [];
    }
}
