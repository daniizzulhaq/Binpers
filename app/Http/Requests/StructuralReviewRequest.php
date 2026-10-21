<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StructuralReviewRequest extends FormRequest
{
    /** Hak atas penilaian tertentu diperiksa di AssessmentPolicy. */
    public function authorize(): bool
    {
        return $this->user()->can('assessments.review-structural');
    }

    public function rules(): array
    {
        return [
            // Catatan wajib saat mengembalikan penilaian untuk revisi.
            'notes' => [$this->routeIs('assessor.reviews.return') ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Catatan pengembalian wajib diisi.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }

    public function attributes(): array
    {
        return ['notes' => 'catatan'];
    }
}
