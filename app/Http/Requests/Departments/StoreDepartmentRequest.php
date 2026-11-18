<?php

namespace App\Http\Requests\Departments;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Department::class);
    }

    protected function prepareForValidation(): void
    {
        // BranchAdmin can only create departments for their own branch
        if ($this->user()->hasRole(UserRole::BranchAdmin->value)) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
            ]);
        }
    }

    public function rules(): array
    {
        $branchId = $this->input('branch_id');

        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departments', 'name')->where(function ($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                }),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
