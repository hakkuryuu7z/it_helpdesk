<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    protected function prepareForValidation(): void
    {
        // BranchAdmin is strictly forced to create users in their own branch and regular_user role
        if ($this->user()->hasRole(UserRole::BranchAdmin->value)) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
                'role' => UserRole::RegularUser->value,
            ]);
        }
    }

    public function rules(): array
    {
        $allowedRoles = $this->user()->hasRole(UserRole::SuperAdmin->value)
            ? array_column(UserRole::cases(), 'value')
            : [UserRole::RegularUser->value];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:50', 'unique:users,username'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['required', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in($allowedRoles)],
            'is_active' => ['boolean'],
            'assigned_branches' => ['nullable', 'array'],
            'assigned_branches.*' => ['exists:branches,id'],
        ];
    }
}
