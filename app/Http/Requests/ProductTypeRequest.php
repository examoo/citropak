<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductTypeRequest extends FormRequest
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
        $id = null;
        if ($type = $this->route('product_type')) {
            $id = $type instanceof \App\Models\ProductType ? $type->id : $type;
        }

        return [
            'name' => 'required|string|max:255|unique:product_types,name,' . $id,
        ];
    }
}
