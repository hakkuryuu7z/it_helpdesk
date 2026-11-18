<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    protected function prepareForValidation(): void
    {
        $targetUser = $this->route('user');

        // Non-SuperAdmin cannot alter branch or role
        if (!$this->user()->hasRole(UserRole::SuperAdmin->value)) {
            $this->merge([
                'branch_id' => $targetUser->branch_id,
                'role' => $targetUser->roles->first()?->name ?? UserRole::RegularUser->value,
            ]);
        }
    }

    public function rules(): array
    {
        $targetUser = $this->route('user');
        $allowedRoles = array_column(UserRole::cases(), 'value');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($targetUser->id)],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($targetUser->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['required', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', Rule::in($allowedRoles)],
            'is_active' => ['boolean'],
            'assigned_branches' => ['nullable', 'array'],
            'assigned_branches.*' => ['exists:branches,id'],
        ];
    }
}
