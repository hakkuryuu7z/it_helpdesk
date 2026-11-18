<?php

namespace App\Http\Requests\Departments;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('department'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()->hasRole(UserRole::BranchAdmin->value)) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
            ]);
        }
    }

    public function rules(): array
    {
        $department = $this->route('department');
        $branchId = $this->input('branch_id', $department->branch_id);

        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departments', 'name')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($department->id),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
