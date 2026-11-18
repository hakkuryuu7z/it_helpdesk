<?php

namespace App\Http\Requests\FeatureRequests;

use App\Enums\FeatureRequestStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeatureStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $featureRequest = $this->route('feature_request');
        return $this->user()->can('updateStage', $featureRequest);
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::enum(FeatureRequestStage::class)],
            'dev_priority' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'estimated_effort' => ['nullable', 'string', 'max:50'],
            'technical_notes' => ['nullable', 'string'],
            'testing_notes' => ['nullable', 'string'],
            'deployment_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
