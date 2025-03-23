<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'sku' => [
                'required',
                'string',
                $this->isMethod('PUT') || $this->isMethod('PATCH')
                    ? Rule::unique('products')->ignore($this->route('product'))
                    : Rule::unique('products'),
            ],
            'category_ids' => ['required', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'is_active' => ['boolean'],
        ];

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['name'][0] = 'sometimes';
            $rules['price'][0] = 'sometimes';
            $rules['stock'][0] = 'sometimes';
            $rules['sku'][0] = 'sometimes';
            $rules['category_ids'][0] = 'sometimes';
        }

        return $rules;
    }
}
