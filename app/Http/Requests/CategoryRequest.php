<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true; // You can add authorization logic here if needed
    }

    public function rules()
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->ignore($this->category),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['name'][0] = 'sometimes';
        }

        return $rules;
    }
}
