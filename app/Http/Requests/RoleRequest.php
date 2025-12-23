<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
        if ($role = $this->route('role')) {
            // For Roles, the route param might be an ID if explicit binding isn't set up or if using 'roles/{role}' convention
            // Assuming standard resource binding
            $id = is_object($role) ? $role->id : $role;
        }

        $distributionId = null;
        $userDist = $this->user()->distribution_id ?? session('current_distribution_id');
        
        if ($userDist && $userDist !== 'all') {
            $distributionId = $userDist;
        } else {
            $distributionId = $this->input('distribution_id');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('roles')->where(function ($query) use ($distributionId) {
                    return $query->where('distribution_id', $distributionId)
                                 ->orWhereNull('distribution_id');
                })->ignore($id),
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
            'distribution_id' => 'nullable|exists:distributions,id',
        ];
    }
}
