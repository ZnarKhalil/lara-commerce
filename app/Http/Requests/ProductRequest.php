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
                    : Rule::unique('products')
            ],
            'category_ids' => ['required', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'is_active' => ['boolean']
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

    // public function messages()
    // {
    //     return [
    //         'name.required' => 'The product name is required',
    //         'price.required' => 'The product price is required',
    //         'price.min' => 'The price must be greater than or equal to 0',
    //         'stock.required' => 'The product stock is required',
    //         'stock.min' => 'The stock must be greater than or equal to 0',
    //         'sku.required' => 'The SKU is required',
    //         'sku.unique' => 'This SKU has already been taken',
    //         'category_ids.required' => 'At least one category must be selected',
    //         'category_ids.*.exists' => 'One or more selected categories are invalid'
    //     ];
    // }
} 