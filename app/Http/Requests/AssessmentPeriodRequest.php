<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssessmentPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('periods.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('assessment_periods', 'name')->ignore($this->route('period')?->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
