<?php

namespace App\Http\Requests\Tickets;

use App\Enums\TicketImpact;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Ticket::class);
    }

    protected function prepareForValidation(): void
    {
        // Enforce branch_id server-side for ordinary users
        if (!$this->user()->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TicketType::class)],
            'category_id' => ['required', 'exists:ticket_categories,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'impact' => ['required', Rule::enum(TicketImpact::class)],
            'urgency' => ['required', Rule::enum(TicketUrgency::class)],
            'attachment' => ['nullable', 'file', 'max:10240'], // 10MB
            'business_requirements' => ['nullable', 'string'],
            'problem_description' => ['nullable', 'string'],
            'expected_outcome' => ['nullable', 'string'],
        ];
    }
}
