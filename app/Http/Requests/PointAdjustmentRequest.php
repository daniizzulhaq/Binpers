<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PointAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('rewards.manage');
    }

    public function rules(): array
    {
        return [
            'points' => ['required', 'integer', 'between:-1000,1000', 'not_in:0'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}
