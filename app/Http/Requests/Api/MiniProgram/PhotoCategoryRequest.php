<?php

namespace App\Http\Requests\Api\MiniProgram;


use App\Http\Requests\Api\FormRequest;
use Illuminate\Validation\Rule;

class PhotoCategoryRequest extends FormRequest
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
                            'between:1,25',
                            Rule::unique('photo_categories')->where(function ($query) {
                                $query->where('deleted_at', 0)
                                    ->where('member_id', '=', $this->request->get('memberId'));
                            })
                        ]
                    ];
                }
        }

        return [];
    }
}
