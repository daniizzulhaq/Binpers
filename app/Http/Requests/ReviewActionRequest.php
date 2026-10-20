<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assessments.approve');
    }

    public function rules(): array
    {
        return [
            // Catatan wajib saat mengembalikan penilaian untuk revisi.
            'notes' => [$this->routeIs('review.return') ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }
}
