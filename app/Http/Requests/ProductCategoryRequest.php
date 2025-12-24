<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $categoryId = $this->route('product_category')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')->ignore($categoryId),
            ],
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required.',
            'name.unique' => 'This category name already exists.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Status must be either active or inactive.',
        ];
    }
}
