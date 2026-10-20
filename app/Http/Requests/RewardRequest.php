<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('rewards.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('rewards', 'name')->ignore($this->route('reward')?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'min_points' => ['required', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['boolean'],
        ];
    }
}
