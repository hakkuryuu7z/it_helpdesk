<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        $isInternal = $this->boolean('is_internal');

        if ($isInternal) {
            return $this->user()->can('addInternalNote', $ticket);
        }

        return $this->user()->can('view', $ticket);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2'],
            'is_internal' => ['boolean'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
