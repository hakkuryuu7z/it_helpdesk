<?php

namespace App\Http\Requests\Branches;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Branch::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'name' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
