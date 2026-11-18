<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
