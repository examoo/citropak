<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscountSchemeRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'distribution_id' => 'nullable|exists:distributions,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'scheme_type' => 'required|in:product,brand',
            'product_id' => 'nullable|required_if:scheme_type,product|exists:products,id',
            'brand_id' => 'nullable|required_if:scheme_type,brand|exists:brands,id',
            'from_qty' => 'required|integer|min:1',
            'to_qty' => 'nullable|integer|gte:from_qty',
            'pieces' => 'nullable|integer|min:0',
            'free_product_code' => 'nullable|string|max:255',
            'amount_less' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Scheme name is required.',
            'start_date.required' => 'Start date is required.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'product_id.required_if' => 'Product is required when scheme type is Product.',
            'brand_id.required_if' => 'Brand is required when scheme type is Brand.',
        ];
    }
}
