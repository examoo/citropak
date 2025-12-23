<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_code' => 'nullable|string|max:50',
            'van' => 'nullable|string|max:100',
            'shop_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'sub_address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:100',
            'channel' => 'nullable|string|max:100',
            'ntn_number' => 'nullable|string|max:50',
            'cnic' => 'nullable|string|max:50',
            'sales_tax_number' => 'nullable|string|max:50',
            'distribution_id' => ['nullable', 'exists:distributions,id', function ($attribute, $value, $fail) {
                if (is_null($value) && is_null(auth()->user()->distribution_id) && is_null(session('current_distribution_id'))) {
                     $fail('The distribution field is required.');
                }
            }],
            'sub_distribution' => 'nullable|string|max:100',
            'day' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'atl' => 'nullable|in:active,inactive',
            'adv_tax_percent' => 'nullable|numeric|min:0|max:100',
            'percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
