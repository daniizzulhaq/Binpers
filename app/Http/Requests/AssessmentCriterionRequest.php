<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssessmentCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('criteria.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'min_score' => ['required', 'numeric', 'min:0'],
            'max_score' => ['required', 'numeric', 'gt:min_score', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
